<?php

declare(strict_types=1);

namespace MySQLReplication\Config;

class ConfigBuilder
{
    private string $user = '';

    private string $host = 'localhost';

    private int $port = 3306;

    private string $password = '';

    private string $charset = 'utf8';

    private string $gtid = '';

    private int $slaveId = 666;

    private string $binLogFileName = '';

    private string $binLogPosition = '';

    private array $eventsOnly = [];

    private array $eventsIgnore = [];

    private array $tablesOnly = [];

    private array $databasesOnly = [];

    private array $tablesRegex = [];

    private array $databasesRegex = [];

    private string $mariaDbGtid = '';

    private int $tableCacheSize = 128;

    private array $custom = [];

    private float $heartbeatPeriod = 0.0;

    private string $slaveUuid = '0015d2b6-8a06-4e5e-8c07-206ef3fbd274';

    public function withSlaveUuid(string $slaveUuid): self
    {
        $this->slaveUuid = $slaveUuid;

        return $this;
    }

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

    public function withBinLogPosition(string $binLogPosition): self
    {
        $this->binLogPosition = $binLogPosition;

        return $this;
    }

    public function withEventsOnly(array $eventsOnly): self
    {
        $this->eventsOnly = $eventsOnly;

        return $this;
    }

    /**
     * @param array<int, int> $eventsIgnore
     */
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

    public function withDatabasesRegex(array $databasesRegex): self
    {
        $this->databasesRegex = $databasesRegex;
        return $this;
    }

    public function withTablesRegex(array $tablesRegex): self
    {
        $this->tablesRegex = $tablesRegex;
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
            $this->heartbeatPeriod,
            $this->slaveUuid,
            $this->tablesRegex,
            $this->databasesRegex,
        );
    }
}
