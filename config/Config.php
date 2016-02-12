<?php

/**
 * Class Config
 */
class Config {

    public static $DB_CONFIG = array(
			'username' => 'root',
			'host'     => '192.168.1.100',
			'port'     => '3306',
			'password' => 'root',
			'db_name'  => ''
	);


    public static $BINLOG_COUNT = 100;
    public static $BINLOG_NAME_PATH  = 'file-pos';

    public static $OUT  = 'out.log';

    public static $LOG_ERROR_PATH  = 'log/error.log';
    public static $LOG_WARN_PATH   = 'log/warn.log';
    public static $LOG_NOTICE_PATH = 'log/notice.log';

    public static $LOG = [
        'binlog' => [
            'error' => 'log/binlog-error.log'
        ],
        'mysql'  => [
            'error'  => 'log/mysql-error.log',
            'notice' => 'log/mysql-notice.log',
            'warn'   => 'log/mysql-error.log'
        ],
    ];


	public static function init() {

        self::$BINLOG_NAME_PATH  = ROOT . 'file-pos';
        self::$OUT               = ROOT . 'out.log';
		foreach(self::$LOG as $key => $value) {
		    foreach($value as $k => $v) {
			    self::$LOG[$key][$k] = ROOT . $v;
			}
		}
	}
}

Config::init();

