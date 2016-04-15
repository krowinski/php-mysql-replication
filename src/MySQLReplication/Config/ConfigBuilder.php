<?php

namespace MySQLReplication\Config;

/**
 * Class ConfigBuilder
 * @package MySQLReplication\Config
 */
class ConfigBuilder
{
    /**
     * @var string
     */
    private $user = '';
    /**
     * @var string
     */
    private $host = 'localhost';
    /**
     * @var int
     */
    private $port = 3306;
    /**
     * @var string
     */
    private $password = '';
    /**
     * @var string
     */
    private $dbName = '';
    /**
     * @var string
     */
    private $charset = 'utf8';
    /**
     * @var string
     */
    private $gtid = '';
    /**
     * @var int
     */
    private $slaveId = 666;
    /**
     * @var string
     */
    private $binLogFileName = '';
    /**
     * @var int
     */
    private $binLogPosition = '';
    /**
     * @var array
     */
    private $eventsOnly = [];
    /**
     * @var array
     */
    private $eventsIgnore = [];
    /**
     * @var array
     */
    private $tablesOnly = [];
    /**
     * @var array
     */
    private $databasesOnly = [];

    /**
     * @return Config
     */
    public function build()
    {
        return new Config(
            $this->user,
            $this->host,
            $this->port,
            $this->password,
            $this->dbName,
            $this->charset,
            $this->gtid,
            $this->slaveId,
            $this->binLogFileName,
            $this->binLogPosition,
            $this->eventsOnly,
            $this->eventsIgnore,
            $this->tablesOnly,
            $this->databasesOnly
        );
    }
    /**
     * @param string $user
     */
    public function withUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param string $host
     */
    public function withHost($host)
    {
        $this->host = $host;
    }

    /**
     * @param int $port
     */
    public function withPort($port)
    {
        $this->port = $port;
    }

    /**
     * @param string $password
     */
    public function withPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param string $dbName
     */
    public function withDbName($dbName)
    {
        $this->dbName = $dbName;
    }

    /**
     * @param string $charset
     */
    public function withCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @param string $gtid
     */
    public function withGtid($gtid)
    {
        $this->gtid = $gtid;
    }

    /**
     * @param int $slaveId
     */
    public function withSlaveId($slaveId)
    {
        $this->slaveId = $slaveId;
    }

    /**
     * @param string $binLogFileName
     */
    public function withBinLogFileName($binLogFileName)
    {
        $this->binLogFileName = $binLogFileName;
    }

    /**
     * @param int $binLogPosition
     */
    public function withBinLogPosition($binLogPosition)
    {
        $this->binLogPosition = $binLogPosition;
    }

    /**
     * @param array $eventsOnly
     */
    public function withEventsOnly(array $eventsOnly)
    {
        $this->eventsOnly = $eventsOnly;
    }

    /**
     * @param array $eventsIgnore
     */
    public function withEventsIgnore(array $eventsIgnore)
    {
        $this->eventsIgnore = $eventsIgnore;
    }

    /**
     * @param array $tablesOnly
     */
    public function withTablesOnly(array $tablesOnly)
    {
        $this->tablesOnly = $tablesOnly;
    }

    /**
     * @param array $databasesOnly
     */
    public function withDatabasesOnly(array $databasesOnly)
    {
        $this->databasesOnly = $databasesOnly;
    }

}