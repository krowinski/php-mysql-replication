<?php

require_once "config/Config.php";
require_once 'db/TimeDate.php';
require_once 'db/DBMysql.php';
require_once "db/DBHelper.php";
require_once "db/Log.php";
require_once "const/ConstFieldType.php";
require_once "bin/BinLogPack.php";
require_once "bin/BinLogEvent.php";
require_once "pack/RowEvent.php";
require_once "const/ConstEventType.php";
require_once "bin/BinLogColumns.php";
require_once "const/ConstCommand.php";
require_once "const/ConstAuth.php";
require_once "pack/ServerInfo.php";
require_once "const/ConstCapability.php";
require_once "pack/PackAuth.php";
require_once "const/ConstMy.php";


class Connect {


    private static $_FLAG = 0;

    private static $_SOCKET;
    private static $_USER;
    private static $_PASS;
    private static $_PORT;
    private static $_DB;
    private static $_HOST;

    private static $_SALT;


    // 当前读取到日志pos file
    private static $_POS;
    private static $_FILE;
    // todo gtid方式读取
    private static $_GTID;

    // 模拟从库id 不能和主库冲突
    private static $_SLAVE_SERVER_ID = 100;

    // 持久化file pos文件存储位置
    public static $FILE_POS;

    // checksum是否开启
    private static $_CHECKSUM = false;

    // 持久化file pos 计数器
    private static $_COUNT = 0;

    // 标记连接状态
    private static $_CONNECTION = false;

    /**
     * @param int $pos  开始日志position 默认从4开始
     * @param null $file 开始文件 mysql-bin.000070，默认读取最新binlog，(show master status;)
     * @param bool|false $gtid
     * @param string $slave_id
     */
    public static function init($pos = 4, $file = null, $gtid = false, $slave_id = '') {


        self::$_SLAVE_SERVER_ID = empty($slave_id) ? self::$_SLAVE_SERVER_ID : $slave_id;

        // 复制方式
        self::$_GTID = $gtid;

        // 读取位置
        if($file && $pos) {
            self::$_POS  = $pos;
            self::$_FILE = $file;
        } else{
            // 记录binlog读取到的位置
            self::$FILE_POS = dirname(__FILE__) .'/'.  Config::$BINLOG_NAME_PATH;
            list($filename, $pos) = self::getFilePos();
            if($filename) {
                self::$_POS   = $pos;
                self::$_FILE  = $filename;
            }
        }


        // 认证auth
        self::$_HOST = Config::$DB_CONFIG['host'];
        self::$_USER = Config::$DB_CONFIG['username'];
        self::$_PASS = Config::$DB_CONFIG['password'];
        self::$_PORT = Config::$DB_CONFIG['port'];
        self::$_DB   = Config::$DB_CONFIG['db_name'];


        if(( self::$_SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            throw new BinLogException( sprintf( "Unable to create a socket: %s", socket_strerror( socket_last_error())));
        }
        socket_set_block(self::$_SOCKET);
        socket_set_option(self::$_SOCKET, SOL_SOCKET, SO_KEEPALIVE, 1);
         //socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 5000]);
         //socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 5000]);

        self::$_FLAG = ConstCapability::$CAPABILITIES ;//| S::$MULTI_STATEMENTS;
        if(self::$_DB) {
            self::$_FLAG |= ConstCapability::$CONNECT_WITH_DB;
        }

        //self::$_FLAG |= S::$MULTI_RESULTS;

        // 连接到mysql
        self::_connect();
    }


    private static function _connect() {

        // create socket
        if(!socket_connect(self::$_SOCKET, self::$_HOST, self::$_PORT)) {
            throw new BinLogException(
                sprintf(
                    'error:%s, msg:%s',
                    socket_last_error(),
                    socket_strerror(socket_last_error())
                )
            );
        }

        // 获取server信息
        self::_serverInfo();

        // 认证
        self::auth();

        //
        self::getBinlogStream();

        self::$_CONNECTION = TRUE;
    }



    private static function _write($data) {
        if(socket_write(self::$_SOCKET, $data, strlen($data))=== false )
        {
            throw new BinLogException( sprintf( "Unable to write to socket: %s", socket_strerror( socket_last_error())));
        }
        return true;
    }

    private static function _readBytes($data_len) {

        // server gone away
        if($data_len == 5) {
            throw new BinLogException('read 5 bytes from mysql server has gone away');
        }

        try{
            $bytes_read = 0;
            $body       = '';
            while ($bytes_read < $data_len) {
                $resp = socket_read(self::$_SOCKET, $data_len - $bytes_read);

                // server kill connection or server gone away
				if(strlen($resp) == 0){
						echo 1111111;
                    //self::_goneAway();
                    //throw new BinLogException("read less " . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
			if(strlen($body) < $data_len){
					echo "read less " . ($data_len - strlen($body));
                self::_goneAway();
                throw new BinLogException("read less " . ($data_len - strlen($body)));
            }
            return $body;
		}catch (Exception $e) {
				var_dump($e);
            self::_goneAway();
            throw new BinLogException(var_export($e, true));
        }

    }

    /**
     * @mysql gone away
     */
	private static function _goneAway() {
        Log::error('mysql server has gone away','mysqlBinlog', Config::$LOG['binlog']['error']);
        self::$_CONNECTION = false;
    }

    /**
     * @breif mysql 是否可用
     * @return bool
     */
    private static function connection() {
        return self::$_CONNECTION;
    }


    private static function _readPacket() {
        //消息头
        $header = self::_readBytes(4);

        if($header === false) return false;
        //消息体长度3bytes 小端序
        $a = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
//      $a = (int)(ord($header[0]) & 0xFF);
//      $a += (int)((ord($header[1])& 0xFF) << 8);
//      $a += (int)((ord($header[2])& 0xFF) << 16);

        //序号 1byte 确认消息的顺序
        $pack_num = unpack("C",$header[3])[1];

        $result = self::_readBytes($a);
        //echo '消息长度'.$a.'  read -> '.strlen($result)."\n";
        return $result;
    }

    /**
     * @brief 获取ServerInfo
     * @return void
     */
    private static function _serverInfo() {
        $pack   = self::_readPacket();
        ServerInfo::run($pack);
        // 加密salt
        self::$_SALT = ServerInfo::getSalt();
    }

    private static function auth() {
        // pack拼接
        $data = PackAuth::initPack(self::$_FLAG, self::$_USER, self::$_PASS, self::$_SALT, self::$_DB);

        self::_write($data);
        //
        $result = self::_readPacket();

        // 认证是否成功
        PackAuth::success($result);
    }


    public static function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',$chunk_size, 0x03);
        self::_write($prelude . $sql);
    }


    public static function getBinlogStream() {

        // checksum
        self::$_CHECKSUM = DBHelper::isCheckSum();
        if(self::$_CHECKSUM){
            self::excute("set @master_binlog_checksum= @@global.binlog_checksum");
        }

        self::_writeRegisterSlaveCommand();

        // 开始读取的二进制日志位置
        if(!self::$_FILE) {
            $logInfo = DBHelper::getPos();
            self::$_FILE = $logInfo['File'];
            if(!self::$_POS) {
                self::$_POS = $logInfo['Position'];
            }
        }

        $header   = pack('l', 11 + strlen(self::$_FILE));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', self::$_POS);
        $data .= pack('s', 0);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= self::$_FILE;

        self::_write($data);

        //认证
        $result = self::_readPacket();
        PackAuth::success($result);

    }

    /**
     * @breif 解析binlog
     * @param $checkSum
     */
    public static function analysisBinLog($flag = false) {

        $pack   = self::_readPacket();

        $binlog = BinLogPack::getInstance();
        $result = $binlog->init($pack, self::$_CHECKSUM);

        // debug
        if(DEBUG) {
            echo round(memory_get_usage()/1024/1024, 2).'MB'."\n";
        }

        //持久化当前读到的file pos
        if($flag) {
            return self::_sync($result, $flag);
		}else{
		    if($result) return $result;
		}
    }

    private static function _sync($result, $flag) {

        if($flag) {
            if(!self::putFilePos()) {
                echo 'write file pos fail';exit;
            }
        }
        return $result;
    }

    /**
     * @breif 注册成slave
     * @return void
     */
    private static function _writeRegisterSlaveCommand() {
        $header   = pack('l', 18);

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        self::_write($data);

        $result = self::_readPacket();
        PackAuth::success($result);
    }

    /**
     * @brief 读取当前binlog的位置
     * @return array|bool
     */
    public static function getFilePos() {

        $filename =  $pos = '';
        if(file_exists(self::$FILE_POS)) {
            $data = file_get_contents(self::$FILE_POS);
            list($filename, $pos, $date) = explode("|", $data);
        }
        if($filename && $pos) {
            return array($filename, $pos);
        } else{
            return false;
        }
    }

    /**
     * @brief 写入当前binlog的位置
     * @return array|bool
     */
	public static function putFilePos() {
        list($filename, $pos) = BinLogPack::getFilePos();
        $data = sprintf("%s|%s|%s", $filename, $pos, date('Y-m-d H:i:s'));
        return file_put_contents(self::$FILE_POS, $data);
    }
}
