<?php

namespace MySQLReplication\Repository;

/**
 * Class MySQLRepository
 * @package MySQLReplication\Repository
 */
interface RepositoryInterface
{
    /**
     * @param string $schema
     * @param string $table
     * @return array
     */
    public function getFields($schema, $table);

    /**
     * @return mixed
     */
    public function getConnection();

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