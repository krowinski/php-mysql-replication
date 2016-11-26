<?php

namespace MySQLReplication\Repository;

use Doctrine\DBAL\Connection;

/**
 * Class MySQLRepository
 * @package MySQLReplication\Repository
 */
class MySQLRepository implements RepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * @param string $schema
     * @param string $table
     * @return array
     */
    public function getFields($schema, $table)
    {
        $sql = '
             SELECT
                c.`COLUMN_NAME`,
                c.`COLLATION_NAME`,
                c.`CHARACTER_SET_NAME`,
                c.`COLUMN_COMMENT`,
                c.`COLUMN_TYPE`,
                c.`COLUMN_KEY`,
                `kcu`.`REFERENCED_TABLE_NAME`,
                `kcu`.`REFERENCED_COLUMN_NAME`
            FROM
                `information_schema`.`COLUMNS`   c
            LEFT JOIN
                `information_schema`.KEY_COLUMN_USAGE kcu
            ON
                    c.`TABLE_SCHEMA` = kcu.`TABLE_SCHEMA`
                AND
                    c.`TABLE_NAME` = kcu.`TABLE_NAME`
                AND
                    c.`COLUMN_NAME` = kcu.`COLUMN_NAME`
            WHERE
                    c.`TABLE_SCHEMA` = ?
                AND
                    c.`TABLE_NAME` = ?
       ';

        return $this->getConnection()->fetchAll($sql, [$schema, $table]);
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        if (false === $this->connection->ping())
        {
            $this->connection->close();
            $this->connection->connect();
        }

        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isCheckSum()
    {
        $res = $this->getConnection()->fetchAssoc('SHOW GLOBAL VARIABLES LIKE "BINLOG_CHECKSUM"');

        return isset($res['Value']);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $r = '';
        $versions = $this->getConnection()->fetchAll('SHOW VARIABLES LIKE "version%"');
        if (is_array($versions) && 0 !== count($versions))
        {
            foreach ($versions as $version)
            {
                $r .= $version['Value'];
            }
        }
        return $r;
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
        return $this->getConnection()->fetchAssoc('SHOW MASTER STATUS');
    }
}