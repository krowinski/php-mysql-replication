<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class MySQLRepository implements RepositoryInterface
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    public function getFields(string $database, string $table): array
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

    private function getConnection(): Connection
    {
        if (false === $this->connection->ping()) {
            $this->connection->close();
            $this->connection->connect();
        }

        return $this->connection;
    }

    /**
     * @throws DBALException
     */
    public function isCheckSum(): bool
    {
        $res = $this->getConnection()->fetchAssoc('SHOW GLOBAL VARIABLES LIKE "BINLOG_CHECKSUM"');

        return isset($res['Value']) && $res['Value'] !== 'NONE';
    }

    public function getVersion(): string
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
     * @inheritDoc
     * @throws DBALException
     */
    public function getMasterStatus(): array
    {
        return $this->getConnection()->fetchAssoc('SHOW MASTER STATUS');
    }
}