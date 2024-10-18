<?php
declare(strict_types=1);

namespace MySQLReplication\Config;

class ConfigBuilder
{
    private $user = '';
    private $host = 'localhost';
    private $port = 3306;
    private $password = '';
    private $charset = 'utf8';
    private $gtid = '';
    private $slaveId = 666;
    private $binLogFileName = '';
    private $binLogPosition = 0;
    private $eventsOnly = [];
    private $eventsIgnore = [];
    private $tablesOnly = [];
    private $databasesOnly = [];
    private $mariaDbGtid = '';
    private $tableCacheSize = 128;
    private $custom = [];
    private $heartbeatPeriod = 0.0;

    public function withUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function withHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function withPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function withPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function withCharset(string $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    public function withGtid(string $gtid): self
    {
        $this->gtid = $gtid;

        return $this;
    }

    public function withSlaveId(int $slaveId): self
    {
        $this->slaveId = $slaveId;

        return $this;
    }

    public function withBinLogFileName(string $binLogFileName): self
    {
        $this->binLogFileName = $binLogFileName;

        return $this;
    }

    public function withBinLogPosition(int $binLogPosition): self
    {
        $this->binLogPosition = $binLogPosition;

        return $this;
    }

    public function withEventsOnly(array $eventsOnly): self
    {
        $this->eventsOnly = $eventsOnly;

        return $this;
    }

    public function withEventsIgnore(array $eventsIgnore): self
    {
        $this->eventsIgnore = $eventsIgnore;

        return $this;
    }

    public function withTablesOnly(array $tablesOnly): self
    {
        $this->tablesOnly = $tablesOnly;

        return $this;
    }

    public function withDatabasesOnly(array $databasesOnly): self
    {
        $this->databasesOnly = $databasesOnly;

        return $this;
    }

    public function withMariaDbGtid(string $mariaDbGtid): self
    {
        $this->mariaDbGtid = $mariaDbGtid;

        return $this;
    }


    public function withTableCacheSize(int $tableCacheSize): self
    {
        $this->tableCacheSize = $tableCacheSize;

        return $this;
    }


    public function withCustom(array $custom): self
    {
        $this->custom = $custom;

        return $this;
    }

    /**
     * @see https://dev.mysql.com/doc/refman/5.6/en/change-master-to.html
     */
    public function withHeartbeatPeriod(float $heartbeatPeriod): self
    {
        $this->heartbeatPeriod = $heartbeatPeriod;

        return $this;
    }

    public function build(): Config
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
            $this->custom,
            $this->heartbeatPeriod
        );
    }
}