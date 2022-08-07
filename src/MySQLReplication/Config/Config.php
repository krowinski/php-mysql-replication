<?php
declare(strict_types=1);

namespace MySQLReplication\Config;

use JsonSerializable;

class Config implements JsonSerializable
{
    private $user;
    private $host;
    private $port;
    private $password;
    private $charset;
    private $gtid;
    private $slaveId;
    private $binLogFileName;
    private $binLogPosition;
    private $eventsOnly;
    private $eventsIgnore;
    private $tablesOnly;
    private $databasesOnly;
    private $mariaDbGtid;
    private $tableCacheSize;
    private $custom;
    private $heartbeatPeriod;

    public function __construct(
        string $user,
        string $host,
        int $port,
        string $password,
        string $charset,
        string $gtid,
        string $mariaGtid,
        int $slaveId,
        string $binLogFileName,
        int $binLogPosition,
        array $eventsOnly,
        array $eventsIgnore,
        array $tablesOnly,
        array $databasesOnly,
        int $tableCacheSize,
        array $custom,
        float $heartbeatPeriod
    ) {
        $this->user = $user;
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->charset = $charset;
        $this->gtid = $gtid;
        $this->slaveId = $slaveId;
        $this->binLogFileName = $binLogFileName;
        $this->binLogPosition = $binLogPosition;
        $this->eventsOnly = $eventsOnly;
        $this->eventsIgnore = $eventsIgnore;
        $this->tablesOnly = $tablesOnly;
        $this->databasesOnly = $databasesOnly;
        $this->mariaDbGtid = $mariaGtid;
        $this->tableCacheSize = $tableCacheSize;
        $this->custom = $custom;
        $this->heartbeatPeriod = $heartbeatPeriod;
    }

    /**
     * @throws ConfigException
     */
    public function validate(): void
    {
        if (!empty($this->host)) {
            $ip = gethostbyname($this->host);
            if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new ConfigException(ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE);
            }
        }
        if (!empty($this->port) && false === filter_var(
                $this->port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]
            )) {
            throw new ConfigException(ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE);
        }
        if (!empty($this->gtid)) {
            foreach (explode(',', $this->gtid) as $gtid) {
                if (!(bool)preg_match(
                    '/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches
                )) {
                    throw new ConfigException(ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE);
                }
            }
        }
        if (!empty($this->slaveId) && false === filter_var(
                $this->slaveId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]
            )) {
            throw new ConfigException(ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE);
        }
        if (false === filter_var($this->binLogPosition, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE
            );
        }
        if (false === filter_var($this->tableCacheSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE
            );
        }
        if (0.0 !== $this->heartbeatPeriod && false === (
                $this->heartbeatPeriod >= 0.001 && $this->heartbeatPeriod <= 4294967.0
            )) {
            throw new ConfigException(
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE
            );
        }
    }

    public function getCustom(): array
    {
        return $this->custom;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getGtid(): string
    {
        return $this->gtid;
    }

    public function getMariaDbGtid(): string
    {
        return $this->mariaDbGtid;
    }

    public function getSlaveId(): int
    {
        return $this->slaveId;
    }

    public function getBinLogFileName(): string
    {
        return $this->binLogFileName;
    }

    public function getBinLogPosition(): int
    {
        return $this->binLogPosition;
    }

    public function getTableCacheSize(): int
    {
        return $this->tableCacheSize;
    }

    public function checkDataBasesOnly(string $database): bool
    {
        return [] !== $this->getDatabasesOnly() && !in_array($database, $this->getDatabasesOnly(), true);
    }

    public function getDatabasesOnly(): array
    {
        return $this->databasesOnly;
    }

    public function checkTablesOnly(string $table): bool
    {
        return [] !== $this->getTablesOnly() && !in_array($table, $this->getTablesOnly(), true);
    }

    public function getTablesOnly(): array
    {
        return $this->tablesOnly;
    }

    public function checkEvent(int $type): bool
    {
        if ([] !== $this->getEventsOnly() && !in_array($type, $this->getEventsOnly(), true)) {
            return false;
        }

        if (in_array($type, $this->getEventsIgnore(), true)) {
            return false;
        }

        return true;
    }

    public function getEventsOnly(): array
    {
        return $this->eventsOnly;
    }

    public function getEventsIgnore(): array
    {
        return $this->eventsIgnore;
    }

    public function getHeartbeatPeriod(): float
    {
        return $this->heartbeatPeriod;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}