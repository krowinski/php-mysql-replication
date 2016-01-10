<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/17
 * Time: 下午7:34
 */
class Config {

    public static $DB_CONFIG = array(
			'username' => 'root',
			'host'     => '127.0.0.1',
			'port'     => '3307',
			'password' => '123456',
			'db_name'  => 'zzq'
	);

    // 默认100次mysql dml操作记录一次 pos，filename到文件
    public static $BINLOG_COUNT = 100;

    // 记录当前执行到的pos，filename
    public static $BINLOG_NAME_PATH  = 'file-pos';


    // log记录
    public static $LOG_ERROR_PATH  = 'log/error.log';
    public static $LOG_WARN_PATH   = 'log/warn.log';
    public static $LOG_NOTICE_PATH = 'log/notice.log';


    public static $LOG = [
        'binlog' => [
            'error' => 'log/binlog-error.log'
        ],
        'kafka' => [
            'error'  => 'log/kafka-error.log',
            'notice' => 'log/kafka-notice.log'
        ],
        'mysql'  => [
            'error'  => 'log/mysql-error.log',
            'notice' => 'log/mysql-notice.log',
            'warn'   => 'log/mysql-error.log'
        ],
    ];

}
