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

    /**
     * MySQLRepository constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    /**
     * @param string $database
     * @param string $table
     * @return array
     */
    public function getFields($database, $table)
    {
        $sql = '
             SELECT
                `COLUMN_NAME`,
                `COLLATION_NAME`,
                `CHARACTER_SET_NAME`,
                `COLUMN_COMMENT`,
                `COLUMN_TYPE`,
                `COLUMN_KEY`
            FROM
                `information_schema`.`COLUMNS`
            WHERE
                    `TABLE_SCHEMA` = ?
                AND
                    `TABLE_NAME` = ?
       ';

        return $this->getConnection()->fetchAll($sql, [$database, $table]);
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        if (false === $this->connection->ping()) {
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

		if (!isset($res['Value'])) {
			return false;
		}

		$check_sum = $res['Value'];

		return (!empty($check_sum) && strtolower($check_sum) !== 'none');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $r = '';
        $versions = $this->getConnection()->fetchAll('SHOW VARIABLES LIKE "version%"');
        if (is_array($versions) && 0 !== count($versions)) {
            foreach ($versions as $version) {
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