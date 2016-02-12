<?php

require_once ROOT . "config/Config.php";
require_once ROOT . 'db/TimeDate.php';
require_once ROOT . 'db/DBMysql.php';
require_once ROOT . "db/DBHelper.php";
require_once ROOT . "db/Log.php";
require_once ROOT . "const/ConstFieldType.php";
require_once ROOT . "bin/BinLogPack.php";
require_once ROOT . "bin/BinLogEvent.php";
require_once ROOT . "pack/RowEvent.php";
require_once ROOT . "const/ConstEventType.php";
require_once ROOT . "bin/BinLogColumns.php";
require_once ROOT . "const/ConstCommand.php";
require_once ROOT . "const/ConstAuth.php";
require_once ROOT . "pack/ServerInfo.php";
require_once ROOT . "pack/Gtid.php";
require_once ROOT . "const/ConstCapability.php";
require_once ROOT . "pack/PackAuth.php";
require_once ROOT . "const/ConstMy.php";


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
    private static $_SLAVE_SERVER_ID = 3;

    // 持久化file pos文件存储位置
    public static $FILE_POS;

    // checksum是否开启
    private static $_CHECKSUM = false;



    /**
     * @param int $pos  开始日志position 默认从4开始
     * @param null $file 开始文件 mysql-bin.000070，默认读取最新binlog，(show master status;)
     * @param bool|false $gtid
     * @param string $slave_id
     */
    public static function init($gtid = false, $slave_id = '') {


        self::$_SLAVE_SERVER_ID = empty($slave_id) ? self::$_SLAVE_SERVER_ID : $slave_id;

        // 复制方式
        self::$_GTID = $gtid;




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
    }



    private static function _write($data)
    {
        if(false === socket_write(self::$_SOCKET, $data, strlen($data)))
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

                //
                if($resp === false) {
                    self::_goneAway('remote host has closed the connection');
                    throw new BinLogException(
                        sprintf(
                        'remote host has closed. error:%s, msg:%s',
                        socket_last_error(),
                        socket_strerror(socket_last_error())
                    ));
                }

                // server kill connection or server gone away
                if(strlen($resp) === 0){
                    self::_goneAway('read less data');
                    throw new BinLogException("read less " . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if(strlen($body) < $data_len){
                self::_goneAway('read undone data');
                throw new BinLogException("read less " . ($data_len - strlen($body)));
            }
            return $body;
        }catch (Exception $e) {
            self::_goneAway('socekt read fail!');
            throw new BinLogException(var_export($e, true));
        }

    }

    /**
     * @mysql gone away
     */
	private static function _goneAway($msg) {
        Log::error($msg . 'mysql server has gone away', 'mysqlBinlog', Config::$LOG['binlog']['error']);
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

        /*
        self::$_POS = 1;
        self::$_FILE = 'dupa.bin';

        $header   = pack('l', 11 + strlen(self::$_FILE));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', self::$_POS);
        $data .= pack('s', 0);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= self::$_FILE;
        */



        $COM_BINLOG_DUMP_GTID = 0x1e;

        $Gtid = new GtidSet(self::$_GTID);
        $encoded_data_size = $Gtid->encoded_length();

        $header_size =
            2 +  # binlog_flags
            4 +  # server_id
            4 +  # binlog_name_info_size
            4 +  # empty binlog name
            8 +  # binlog_pos_info_size
            4;

        $prelude = pack('l', $header_size + $encoded_data_size) . chr($COM_BINLOG_DUMP_GTID);
        $prelude .= pack('S', 0);
        $prelude .= pack('I', self::$_SLAVE_SERVER_ID);
        $prelude .= pack('I', 3);
        $prelude .= chr(0);
        $prelude .= chr(0);
        $prelude .= chr(0);
        $prelude .= pack('Q', 4);

        $prelude .= pack('I', $Gtid->encoded_length());
        $prelude .= $Gtid->encoded();


        $field=bin2hex($prelude);
        $field=chunk_split($field,2,"\\x");
        $field= "\\x" . substr($field,0,-2);

     //  echo $field; die(PHP_EOL);

        self::_write($prelude);

        $result = self::_readPacket();

        //var_dump($result);

        PackAuth::success($result);

    }



    /**
     * @breif 解析binlog
     * @param $checkSum
     */
    public static function analysisBinLog($flag = false) {

        $pack   = self::_readPacket();

        //var_dump($pack);

        // 校验数据包格式
        PackAuth::success($pack);

        $binlog = BinLogPack::getInstance();
        $result = $binlog->init($pack, self::$_CHECKSUM);

        // debug
        if(DEBUG) {
            Log::out(round(memory_get_usage()/1024/1024, 2).'MB');
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
                Log::out('write file pos fail');exit;
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
