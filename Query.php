<?php
/**
 * 获取mysql相关信息 执行query
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/17
 * Time: 下午1:17
 */
class Query {

    public static function getFields($schema, $table) {
        $config['username'] = 'root';
        $config['host'] = '127.0.0.1';
        $config['port'] = '3307';
        $config['password'] = '123456';
        $db  = DBMysql::createDBHandle($config, 'zzq');
        $sql = "SELECT
                COLUMN_NAME,COLLATION_NAME,CHARACTER_SET_NAME,COLUMN_COMMENT,COLUMN_TYPE,COLUMN_KEY
                FROM
                information_schema.columns
                WHERE
                table_schema = '{$schema}' AND table_name = '{$table}'";
        $result = DBMysql::query($db,$sql);

        return $result;
    }
}
