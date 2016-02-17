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
    private $conn;

    public function __construct(Config $config)
    {
        $config = [
            'dbname' => '',
            'user' => $config->getUser(),
            'password' => $config->getPassword(),
            'host' => $config->getHost(),
            'port' => $config->getPort(),
            'driver' => 'pdo_mysql',
        ];
        $this->conn = DriverManager::getConnection($config);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @param string $schema
     * @param string $table
     * @return array
     */
    public function getFields($schema, $table)
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
        return $this->conn->fetchAll($sql, [$schema, $table]);
    }

    /**
     * @return bool
     */
    public function isCheckSum()
    {
        $sql = "SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'";
        $res = $this->conn->fetchAssoc($sql);
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
    public function getMasterStatus()
    {
        $sql = "SHOW MASTER STATUS";
        return $this->conn->fetchAssoc($sql);
    }
}
