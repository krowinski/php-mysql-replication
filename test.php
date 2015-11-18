<?php
header("Content-type:text/html;charset=utf-8");

require_once "Config.php";
require_once 'TimeDate.php';
require_once 'DBMysql.php';
require_once "DbHelper.php";
require_once "FieldType.php";
require_once "BinLogPack.php";
require_once "BinLogEvent.php";
require_once "RowEvent.php";
require_once "EventType.php";
require_once "Columns.php";
require_once "Command.php";

class MyConst {
    # Constants from PyMYSQL source code
    const NULL_COLUMN = 251;
    const UNSIGNED_CHAR_COLUMN = 251;
    const UNSIGNED_SHORT_COLUMN = 252;
    const UNSIGNED_INT24_COLUMN = 253;
    const UNSIGNED_INT64_COLUMN = 254;
    const UNSIGNED_CHAR_LENGTH = 1;
    const UNSIGNED_SHORT_LENGTH = 2;
    const UNSIGNED_INT24_LENGTH = 3;
    const UNSIGNED_INT64_LENGTH = 8;
}


class S
{
    public static $LONG_PASSWORD;
    public static $FOUND_ROWS;
    public static $LONG_FLAG;
    public static $CONNECT_WITH_DB;
    public static $NO_SCHEMA;
    public static $COMPRESS;
    public static $ODBC;
    public static $LOCAL_FILES;
    public static $IGNORE_SPACE;
    public static $PROTOCOL_41;
    public static $INTERACTIVE;
    public static $SSL;
    public static $IGNORE_SIGPIPE;
    public static $TRANSACTIONS;
    public static $SECURE_CONNECTION;
    public static $MULTI_STATEMENTS;
    public static $MULTI_RESULTS;
    public static $CAPABILITIES;

    public static function init() {
        self::$LONG_PASSWORD = 1;
        self::$FOUND_ROWS = 1 << 1;
        self::$LONG_FLAG = 1 << 2;
        self::$CONNECT_WITH_DB = 1 << 3;
        self::$NO_SCHEMA = 1 << 4;
        self::$COMPRESS = 1 << 5;
        self::$ODBC = 1 << 6;
        self::$LOCAL_FILES = 1 << 7;
        self::$IGNORE_SPACE = 1 << 8;
        self::$PROTOCOL_41 = 1 << 9;
        self::$INTERACTIVE = 1 << 10;
        self::$SSL = 1 << 11;
        self::$IGNORE_SIGPIPE = 1 << 12;
        self::$TRANSACTIONS = 1 << 13;
        self::$SECURE_CONNECTION = 1 << 15;
        self::$MULTI_STATEMENTS = 1 << 16;
        self::$MULTI_RESULTS = 1 << 17;
        self::$CAPABILITIES = (self::$LONG_PASSWORD | self::$LONG_FLAG | self::$TRANSACTIONS |
            self::$PROTOCOL_41 | self::$SECURE_CONNECTION);
    }
}
S::init();

class mysqlc {

    // Capabilities 4bytes ，https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
    private static $_flag=0;
    private static $_salt;

    private static $_socket;
    private static $_user;
    private static $_pass;
    private static $_port;
    private static $_db;
    private static $_host;

    private static $_protocol_version;
    private static $_server_version;
    private static $_connection_id;
    private static $_character_set;

    private static $_pack;
    private static $_pack_key = 0;

    // 日志pos file
    private static $_pos;
    private static $_file;

    // 数据库配置 获取master binlog pos、file、checksum...
    public static $DB_CONFIG;


    /**
     * @param int $pos log默认开始位置
     * @param null $file
     */
    public function __construct($pos = 4, $file = null) {


        // 初始化日志位置
        if($pos) self::$_pos = $pos;
        if($file) self::$_file = $file;

        // 认证auth
        self::$_host = Config::$DB_CONFIG['host'];
        self::$_user = Config::$DB_CONFIG['username'];
        self::$_pass = Config::$DB_CONFIG['password'];
        self::$_port = Config::$DB_CONFIG['port'];
        self::$_db   = Config::$DB_CONFIG['db_name'];

        self::$_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_block(self::$_socket);
        socket_set_option(self::$_socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        // socket_set_option(self::$_socket,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 500000]);
        // socket_set_option(self::$_socket,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 500000]);

        self::$_flag |= S::$CAPABILITIES ;//| S::$MULTI_STATEMENTS;
        self::$_flag |= S::$CONNECT_WITH_DB;
        //self::$_flag |= S::$CAPABILITIES;
        //self::$_flag |= S::$MULTI_RESULTS;

        // 连接到mysql
        $this->_connect();
    }


    private function _connect() {
        socket_connect(self::$_socket, self::$_host, self::$_port);
        //
        self::_serverInfo();
        self::auth();
    }



    private static function _write($data) {
        return socket_write(self::$_socket, $data, strlen($data));
    }

    private static function _readBytes($len) {
        return socket_read(self::$_socket, $len);
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

        return self::_readBytes($a);
    }

    private static function _serverInfo() {

        $i = 0;
        $pack   = self::_readPacket();
        $length = strlen($pack);
        self::$_protocol_version = ord($pack[$i]);
        $i++;

        //version
        $start = $i;
        for($i = $start; $i < $length; $i++)
        {
            if($pack[$i] === chr(0)) {
                $i++;
                break;
            } else{
                self::$_server_version .= $pack[$i];
            }
        }

        //connection_id 4 bytes
        self::$_connection_id = $pack[$i]. $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        for($j = $i;$j<$i+8;$j++) {
            self::$_salt .= $pack[$j];
        }
        $i = $i + 8;


        //filler_1 (1) -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i = $i + 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        self::$_character_set = $pack[$i];
        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i = $i + 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i = $i + 2;

        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($pack[$i]);
        $i++;

        $salt_len = max(12, $salt_len - 9);

        //填充值
        $i = $i + 10;

        //next salt
        if ($length >= $i + $salt_len)
        {
            for($j = $i ;$j < $i + $salt_len;$j++)
            {
                self::$_salt .= $pack[$j];
            }

        }

        $auth_plugin_name = '';
        $i = $i + $salt_len + 1;
        for($j = $i;$j<$length-1;$j++) {
            $auth_plugin_name .=$pack[$j];
        }
    }

    private static function auth() {
        //4bytes capability flags
        $data = pack('L',self::$_flag);

        //max-length 4bytes，最大16M 占3bytes
        $data .= pack('L', 2^24 - 1);
        //var_dump(bin2hex(pack('L', 16777219)));exit;
        // Charset  1byte
        $data .=chr(8);
        //$data = pack("iIC", self::$_flag, 16777216, 8);

        //var_dump(bin2hex(pack('@c23','')));exit;

        // 空 bytes23
        for($i=0;$i<23;$i++){
            $data .=chr(0);
        }

        $result = sha1(self::$_pass, true) ^ sha1(self::$_salt . sha1(sha1(self::$_pass, true), true),true);
        //转码 8是 latin1
        self::$_user = iconv('utf8', 'latin1', self::$_user);
        $data = $data . self::$_user . chr(0) . chr(strlen($result)) . $result;
        if(self::$_db) {
            $data .= self::$_db . chr(0);
        }

        $str = pack("L", strlen($data));//  V L 小端，little endian
        $s =$str[0].$str[1].$str[2];

        $data = $s . chr(1) . $data;

        self::_write($data);
        $result = self::_readPacket();
//        var_dump(bin2hex($result));
//        exit;
    }


    public static function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',$chunk_size, 0x03);
        self::_write($prelude . $sql);

    }


    public static function getBinlogStream()
    {

        // checksum
        if(DbHelper::isCheckSum()){
            self::excute("set @master_binlog_checksum= @@global.binlog_checksum");
            //self::_readPacket();
        }


        // 开始读取的二进制日志位置
        if(!self::$_file) {
            $logInfo = DbHelper::getPos();
            self::$_file = $logInfo['File'];
            if(!self::$_pos)
             self::$_pos  = $logInfo['Position'];
        }

        $header   = pack('l', 11 + strlen(self::$_file));

        // COM_BINLOG_DUMP
        $data = $header . chr(Command::COM_BINLOG_DUMP);
        $data .= pack('L', self::$_pos);
        $data .= pack('s', 0);
        $data .= pack('L', 3);
        $data .= self::$_file;

        self::_write($data);

        self::_readPacket();

        while (1) {
            self::$_pack = self::_readPacket();
            $binlog = BinLogPack::getInstance();
            $binlog->init(self::$_pack);
        }
    }


    public static function unpackUint16($data) {
        return unpack("S",$data[0] . $data[1])[1];
    }

    public static function unpackUint24($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        return $a;
    }

}


$mysql = new mysqlc();
mysqlc::getBinlogStream();