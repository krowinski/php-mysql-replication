<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Exception\MySQLReplicationException;

class MySQLRepository implements RepositoryInterface, PingableConnection
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

        return FieldDTOCollection::makeFromArray($this->getConnection()->fetchAllAssociative($sql, [$database, $table]));
    }

    private function getConnection(): Connection
    {
        if (false === $this->ping($this->connection)) {
            $this->connection->close();
            $this->connection->connect();
        }

        return $this->connection;
    }

    /**
     * @throws Exception
     */
    public function isCheckSum(): bool
    {
        $res = $this->getConnection()->fetchAssociative('SHOW GLOBAL VARIABLES LIKE "BINLOG_CHECKSUM"');

        return isset($res['Value']) && $res['Value'] !== 'NONE';
    }

    public function getVersion(): string
    {
        $r = '';
        $versions = $this->getConnection()->fetchAllAssociative('SHOW VARIABLES LIKE "version%"');
        if (is_array($versions) && 0 !== count($versions)) {
            foreach ($versions as $version) {
                $r .= $version['Value'];
            }
        }

        return $r;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws BinLogException
     */
    public function getMasterStatus(): MasterStatusDTO
    {
        $data = $this->getConnection()->fetchAssociative('SHOW MASTER STATUS');
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
        } catch (Exception $e) {
            return false;
        }
    }
}
