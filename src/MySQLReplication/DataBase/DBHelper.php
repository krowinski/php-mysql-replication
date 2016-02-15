<?php

namespace MySQLReplication\DataBase;

use Doctrine\DBAL\DriverManager;
use MySQLReplication\Config\Config;

/**
 * Class DBHelper
 * @package MySQLReplication\DataBase
 */
class DBHelper
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $conn;

    /**
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getConnection()
    {
        if (!isset(self::$conn))
        {
            $config = [
                'dbname' => '',
                'user' => Config::$DB_CONFIG['user'],
                'password' => Config::$DB_CONFIG['password'],
                'host' => Config::$DB_CONFIG['host'],
                'port' => Config::$DB_CONFIG['port'],
                'driver' => 'pdo_mysql',
            ];
            self::$conn = DriverManager::getConnection($config);
        }
        return self::$conn;
    }

    /**
     * @param string $schema
     * @param string $table
     * @return array
     */
    public static function getFields($schema, $table)
    {
        $sql = "
            SELECT
                `COLUMN_NAME`,
                `COLLATION_NAME`,
                `CHARACTER_SET_NAME`,
                `COLUMN_COMMENT`,
                `COLUMN_TYPE`,
                `COLUMN_KEY`
            FROM
                `information_schema`.`columns`
            WHERE
              `table_schema` = ?
            AND
              `table_name` = ?
       ";
        return self::getConnection()->fetchAll($sql, [$schema, $table]);
    }

    /**
     * @return bool
     */
    public static function isCheckSum()
    {
        $sql = "SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'";
        $res = self::getConnection()->fetchAssoc($sql);
        if ($res['Value'])
        {
            return true;
        }
        return false;
    }

    /**
     * File
     * Position
     * Binlog_Do_DB
     * Binlog_Ignore_DB
     * Executed_Gtid_Set
     *
     * @return array
     */
    public static function getMasterStatus()
    {
        $sql = "SHOW MASTER STATUS";
        return self::getConnection()->fetchAssoc($sql);
    }
}
