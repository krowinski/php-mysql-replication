<?php
/**
 * Capability Flags
 * http://dev.mysql.com/doc/internals/en/capability-flags.html#packet-Protocol::CapabilityFlags
 * https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
 * Created by PhpStorm.
 * User: zhaozhiqiang
 * Date: 15/11/19
 * Time: 下午2:54
 */
class ConstCapability {

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
ConstCapability::init();