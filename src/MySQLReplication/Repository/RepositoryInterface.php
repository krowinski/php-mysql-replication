<?php

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
    public function getFields($database, $table);

    /**
     * @return bool
     */
    public function isCheckSum();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * File
     * Position
     * Binlog_Do_DB
     * Binlog_Ignore_DB
     * Executed_Gtid_Set
     *
     * @return array
     */
    public function getMasterStatus();
}