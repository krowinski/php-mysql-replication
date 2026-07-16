<?php

declare(strict_types=1);

namespace MySQLReplication\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Exception\MySQLReplicationException;

class MySQLRepository implements RepositoryInterface, PingableConnection
{
    private ?string $version = null;

    public function __construct(
        private readonly Connection $connection
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

        return FieldDTOCollection::makeFromArray($this->getConnection() ->fetchAllAssociative($sql, [$database, $table]));
    }

    public function isCheckSum(): bool
    {
        $res = $this->getConnection()
            ->fetchAssociative('SHOW GLOBAL VARIABLES LIKE "BINLOG_CHECKSUM"');

        return isset($res['Value']) && $res['Value'] !== 'NONE';
    }

    public function isRowFormat(): bool
    {
        $res = $this->getConnection()
            ->fetchAssociative('SHOW GLOBAL VARIABLES LIKE "binlog_format"');

        return isset($res['Value']) && $res['Value'] === 'ROW';
    }

    public function isRowImageFull(): bool
    {
        $res = $this->getConnection()
            ->fetchAssociative('SHOW GLOBAL VARIABLES LIKE "binlog_row_image"');

        // versions without this variable have no partial row image mode
        return !isset($res['Value']) || $res['Value'] === 'FULL';
    }

    public function getVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }

        $res = $this->getConnection()
            ->fetchAssociative('SHOW VARIABLES LIKE "version"');

        return $this->version = (isset($res['Value']) && is_scalar($res['Value']) ? (string)$res['Value'] : '');
    }

    public function getGtidExecuted(): string
    {
        // MariaDB tracks its GTID position in gtid_current_pos; MySQL uses GTID_EXECUTED
        $sql = str_contains($this->getVersion(), 'MariaDB')
            ? 'SELECT @@GLOBAL.gtid_current_pos AS Gtid_Executed'
            : 'SELECT @@GLOBAL.GTID_EXECUTED AS Gtid_Executed';

        $res = $this->getConnection()
            ->fetchAssociative($sql);

        return isset($res['Gtid_Executed']) && is_scalar($res['Gtid_Executed']) ? (string)$res['Gtid_Executed'] : '';
    }

    public function isSemiSyncEnabled(): bool
    {
        // MySQL 8.0.26 renamed rpl_semi_sync_master_enabled to rpl_semi_sync_source_enabled
        $rows = $this->getConnection()
            ->fetchAllAssociative("SHOW GLOBAL VARIABLES WHERE Variable_name IN ('rpl_semi_sync_master_enabled', 'rpl_semi_sync_source_enabled')");

        foreach ($rows as $row) {
            if (isset($row['Value']) && $row['Value'] === 'ON') {
                return true;
            }
        }

        return false;
    }

    public function getMasterStatus(): MasterStatusDTO
    {
        $query = 'SHOW MASTER STATUS';

        if (version_compare($this->getVersion(), '8.4.0') >= 0) {
            $query = 'SHOW BINARY LOG STATUS';
        }

        $data = $this->getConnection()
            ->fetchAssociative($query);
        if ($data === false || $data === []) {
            throw new BinLogException(MySQLReplicationException::BINLOG_NOT_ENABLED, MySQLReplicationException::BINLOG_NOT_ENABLED_CODE);
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
        // In DBAL 4.x, connections handle reconnection automatically
        // No need for manual ping/reconnect logic
        return $this->connection;
    }
}
