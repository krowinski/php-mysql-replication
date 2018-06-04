<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

/**
 * Interface RepositoryInterface
 * @package MySQLReplication\Repository
 */
interface RepositoryInterface
{
    /**
     * @param string $database
     * @param string $table
     * @return array
     */
    public function getFields(string $database, string $table): array;

    /**
     * @return bool
     */
    public function isCheckSum(): bool;

    /**
     * @return string
     */
    public function getVersion(): string;

    /**
     * File
     * Position
     * Binlog_Do_DB
     * Binlog_Ignore_DB
     * Executed_Gtid_Set
     *
     * @return array
     */
    public function getMasterStatus(): array;
}