<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

interface RepositoryInterface
{
    //TODO return VO
    public function getFields(string $database, string $table): array;
    public function isCheckSum(): bool;
    public function getVersion(): string;

    /**
     * TODO - return VO
     * Returns array with:
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