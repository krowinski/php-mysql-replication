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

    // 默认100次mysql操作记录一次 pos，filename到文件
    public static $BINLOG_COUNT = 100;
    // 记录的文件名字
    public static $BINLOG_NAME_PATH  = 'file-pos';


}