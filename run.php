<?php
error_reporting(E_ERROR);
date_default_timezone_set("PRC");
require_once "Config.php";
require_once 'TimeDate.php';
require_once 'DBMysql.php';
require_once "DbHelper.php";
require_once "FieldType.php";
require_once "BinLogPack.php";
require_once "BinLogEvent.php";
require_once "RowEvent.php";
require_once "ConstEventType.php";
require_once "Columns.php";
require_once "ConstCommand.php";
require_once "ConstAuth.php";
require_once "ServerInfo.php";
require_once "Capability.php";
require_once "AuthPack.php";
require_once "ConstMy.php";


class mysqlc {

    
    private static $_FLAG = 0;

    private static $_SOCKET;
    private static $_USER;
    private static $_PASS;
    private static $_PORT;
    private static $_DB;
    private static $_HOST;

    private static $_SALT;

    private static $_PACK;


    // 日志pos file
    private static $_POS;
    private static $_FILE;

    // 模拟从库id 不能和主库冲突
    private static $_SLAVE_SERVER_ID = 10;
    private static $_GTID;

    /**
     * @param int $pos  开始日志position 默认从4开始
     * @param null $file 开始文件 mysql-bin.000070，默认从最新更新文件
     * @param bool|false $gtid
     * @param string $slave_id
     */
    public function __construct($pos = 4, $file = null, $gtid = false, $slave_id = '') {


        self::$_SLAVE_SERVER_ID = empty($slave_id) ? self::$_SLAVE_SERVER_ID : $slave_id;

        // 复制方式
        self::$_GTID = $gtid;

        // 初始化日志位置
        if($pos) self::$_POS   = $pos;
        if($file) self::$_FILE = $file;

        // 认证auth
        self::$_HOST = Config::$DB_CONFIG['host'];
        self::$_USER = Config::$DB_CONFIG['username'];
        self::$_PASS = Config::$DB_CONFIG['password'];
        self::$_PORT = Config::$DB_CONFIG['port'];
        self::$_DB   = Config::$DB_CONFIG['db_name'];

        self::$_SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_block(self::$_SOCKET);
        socket_set_option(self::$_SOCKET, SOL_SOCKET, SO_KEEPALIVE, 1);
        // socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 500000]);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 500000]);

        self::$_FLAG = Capability::$CAPABILITIES ;//| S::$MULTI_STATEMENTS;
        if(self::$_DB) {
            self::$_FLAG |= Capability::$CONNECT_WITH_DB;
        }
        
        //self::$_FLAG |= S::$MULTI_RESULTS;

        // 连接到mysql
        $this->_connect();
    }


    private function _connect() {

        socket_connect(self::$_SOCKET, self::$_HOST, self::$_PORT);

        // 获取server信息
        self::_serverInfo();
        // 认证
        self::auth();
    }



    private static function _write($data) {
        return socket_write(self::$_SOCKET, $data, strlen($data));
    }

    private static function _readBytes($data_len) {

        $bytes_read = 0;
        $body       = '';
        while ($bytes_read < $data_len) {
            $resp = socket_read(self::$_SOCKET, $data_len - $bytes_read);
            $body .= $resp;
            $bytes_read += strlen($resp);
        }
        return $body;
    }


    private static function _readPacket() {
        //消息头
        $header = self::_readBytes(4);

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
        $data = AuthPack::initPack(self::$_FLAG, self::$_USER, self::$_PASS, self::$_SALT, self::$_DB);

        self::_write($data);
        //
        $result = self::_readPacket();

        // 认证是否成功
        AuthPack::success($result);
    }


    public static function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',$chunk_size, 0x03);
        self::_write($prelude . $sql);
    }


    public static function getBinlogStream() {

        // checksum
        $checkSum = DbHelper::isCheckSum();
        if($checkSum){
            self::excute("set @master_binlog_checksum= @@global.binlog_checksum");
        }

        self::_writeRegisterSlaveCommand();

        // 开始读取的二进制日志位置
        if(!self::$_FILE) {
            $logInfo = DbHelper::getPos();
            self::$_FILE = $logInfo['File'];
            if(!self::$_POS)
             self::$_POS = $logInfo['Position'];
        }

        $header   = pack('l', 11 + strlen(self::$_FILE));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', self::$_POS);
        $data .= pack('s', 0);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= self::$_FILE;

        self::_write($data);

        //
        $result = self::_readPacket();
        AuthPack::success($result);

        while (1) {
            self::$_PACK = self::_readPacket();
            $binlog = BinLogPack::getInstance();
            $result = $binlog->init(self::$_PACK, $checkSum);
            if($result !== null)
                var_export($result);
        }
    }

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
        AuthPack::success($result);
    }
}


$mysql = new mysqlc();
mysqlc::getBinlogStream();