<?php

namespace MySQLReplication\Config;

/**
 * Class Config
 * @package MySQLReplication\Config
 */
class Config
{
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $dbName;
    /**
     * @var string
     */
    private $charset;
    /**
     * @var string
     */
    private $gtid;
    /**
     * @var int
     */
    private $slaveId;
    /**
     * @var string
     */
    private $binLogFileName;
    /**
     * @var int
     */
    private $binLogPosition;
    /**
     * @var array
     */
    private $eventsOnly;
    /**
     * @var array
     */
    private $eventsIgnore;
    /**
     * @var array
     */
    private $tablesOnly;
    /**
     * @var array
     */
    private $databasesOnly;

    /**
     * Config constructor.
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $password
     * @param string $dbName
     * @param string $charset
     * @param string $gtid
     * @param int $slaveId
     * @param string $binLogFileName
     * @param $binLogPosition
     * @param array $eventsOnly
     * @param array $eventsIgnore
     * @param array $tablesOnly
     * @param array $databasesOnly
     */
    public function __construct(
        $user,
        $host,
        $port,
        $password,
        $dbName,
        $charset,
        $gtid,
        $slaveId,
        $binLogFileName,
        $binLogPosition,
        array $eventsOnly,
        array $eventsIgnore,
        array $tablesOnly,
        array $databasesOnly
    ) {
        $this->user = $user;
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->charset = $charset;
        $this->gtid = $gtid;
        $this->slaveId = $slaveId;
        $this->binLogFileName = $binLogFileName;
        $this->binLogPosition = $binLogPosition;
        $this->eventsOnly = $eventsOnly;
        $this->eventsIgnore = $eventsIgnore;
        $this->tablesOnly = $tablesOnly;
        $this->databasesOnly = $databasesOnly;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getGtid()
    {
        return $this->gtid;
    }

    /**
     * @return int
     */
    public function getSlaveId()
    {
        return $this->slaveId;
    }

    /**
     * @return string
     */
    public function getBinLogFileName()
    {
        return $this->binLogFileName;
    }

    /**
     * @return int
     */
    public function getBinLogPosition()
    {
        return $this->binLogPosition;
    }

    /**
     * @return array
     */
    public function getEventsOnly()
    {
        return $this->eventsOnly;
    }

    /**
     * @return array
     */
    public function getEventsIgnore()
    {
        return $this->eventsIgnore;
    }

    /**
     * @return array
     */
    public function getTablesOnly()
    {
        return $this->tablesOnly;
    }

    /**
     * @return array
     */
    public function getDatabasesOnly()
    {
        return $this->databasesOnly;
    }

}