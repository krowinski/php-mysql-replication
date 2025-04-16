<?php

declare(strict_types=1);

namespace MySQLReplication\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Exception\MySQLReplicationException;

readonly class MySQLRepository implements RepositoryInterface, PingableConnection
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    public function getFields(string $database, string $table): FieldDTOCollection
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
            ORDER BY 
                ORDINAL_POSITION        
       ';

        return FieldDTOCollection::makeFromArray(
            $this->getConnection()
                ->fetchAllAssociative($sql, [$database, $table])
        );
    }

    public function isCheckSum(): bool
    {
        $res = $this->getConnection()
            ->fetchAssociative('SHOW GLOBAL VARIABLES LIKE "BINLOG_CHECKSUM"');

        return isset($res['Value']) && $res['Value'] !== 'NONE';
    }

    public function getVersion(): string
    {
        $r = '';
        $versions = $this->getConnection()
            ->fetchAllAssociative('SHOW VARIABLES LIKE "version%"');

        foreach ($versions as $version) {
            $r .= $version['Value'];
        }

        return $r;
    }

    public function getMasterStatus(): MasterStatusDTO
    {
        $query = 'SHOW MASTER STATUS';

        if (str_starts_with($this->getVersion(), '8.4')) {
            $query = 'SHOW BINARY LOG STATUS';
        }

        $data = $this->getConnection()
            ->fetchAssociative($query);
        if (empty($data)) {
            throw new BinLogException(
                MySQLReplicationException::BINLOG_NOT_ENABLED,
                MySQLReplicationException::BINLOG_NOT_ENABLED_CODE
            );
        }

        return MasterStatusDTO::makeFromArray($data);
    }

    public function ping(Connection $connection): bool
    {
        try {
            $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
            return true;
        } catch (Exception) {
            return false;
        }
    }

    private function getConnection(): Connection
    {
        if ($this->ping($this->connection) === false) {
            $this->connection->close();
            $this->connection->connect();
        }

        return $this->connection;
    }
}
