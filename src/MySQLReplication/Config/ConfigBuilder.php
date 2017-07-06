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
    private $binLogPosition = 0;
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
     * @var string
     */
    private $mariaDbGtid = '';
    /**
     * @var int
     */
    private $tableCacheSize = 128;
    /**
     * @var array
     */
    private $custom = [];

    /**
     * @param string $user
     * @return ConfigBuilder
     */
    public function withUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param string $host
     * @return ConfigBuilder
     */
    public function withHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param int $port
     * @return ConfigBuilder
     */
    public function withPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param string $password
     * @return ConfigBuilder
     */
    public function withPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param string $charset
     * @return ConfigBuilder
     */
    public function withCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @param string $gtid
     * @return ConfigBuilder
     */
    public function withGtid($gtid)
    {
        $this->gtid = $gtid;

        return $this;
    }

    /**
     * @param int $slaveId
     * @return ConfigBuilder
     */
    public function withSlaveId($slaveId)
    {
        $this->slaveId = $slaveId;

        return $this;
    }

    /**
     * @param string $binLogFileName
     * @return ConfigBuilder
     */
    public function withBinLogFileName($binLogFileName)
    {
        $this->binLogFileName = $binLogFileName;

        return $this;
    }

    /**
     * @param int $binLogPosition
     * @return ConfigBuilder
     */
    public function withBinLogPosition($binLogPosition)
    {
        $this->binLogPosition = $binLogPosition;

        return $this;
    }

    /**
     * @see ConstEventType
     * @param array $eventsOnly
     * @return ConfigBuilder
     */
    public function withEventsOnly(array $eventsOnly)
    {
        $this->eventsOnly = $eventsOnly;

        return $this;
    }

    /**
     * @see ConstEventType
     * @param array $eventsIgnore
     * @return ConfigBuilder
     */
    public function withEventsIgnore(array $eventsIgnore)
    {
        $this->eventsIgnore = $eventsIgnore;

        return $this;
    }

    /**
     * @param array $tablesOnly
     * @return ConfigBuilder
     */
    public function withTablesOnly(array $tablesOnly)
    {
        $this->tablesOnly = $tablesOnly;

        return $this;
    }

    /**
     * @param array $databasesOnly
     * @return ConfigBuilder
     */
    public function withDatabasesOnly(array $databasesOnly)
    {
        $this->databasesOnly = $databasesOnly;

        return $this;
    }

    /**
     * @param string $mariaDbGtid
     * @return ConfigBuilder
     */
    public function withMariaDbGtid($mariaDbGtid)
    {
        $this->mariaDbGtid = $mariaDbGtid;

        return $this;
    }

    /**
     * @param int $tableCacheSize
     */
    public function withTableCacheSize($tableCacheSize)
    {
        $this->tableCacheSize = $tableCacheSize;
    }

    /**
     * @param array $custom
     */
    public function withCustom(array $custom)
    {
        $this->custom = $custom;
    }

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
            $this->charset,
            $this->gtid,
            $this->mariaDbGtid,
            $this->slaveId,
            $this->binLogFileName,
            $this->binLogPosition,
            $this->eventsOnly,
            $this->eventsIgnore,
            $this->tablesOnly,
            $this->databasesOnly,
            $this->tableCacheSize,
            $this->custom
        );
    }
}