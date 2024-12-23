<?php

declare(strict_types=1);

namespace MySQLReplication\Config;

use JsonSerializable;

readonly class Config implements JsonSerializable
{
    public function __construct(
        public string $user,
        public string $host,
        public int $port,
        public string $password,
        public string $charset,
        public string $gtid,
        public string $mariaDbGtid,
        public int $slaveId,
        public string $binLogFileName,
        public string $binLogPosition,
        public array $eventsOnly,
        public array $eventsIgnore,
        public array $tablesOnly,
        public array $databasesOnly,
        public int $tableCacheSize,
        public array $custom,
        public float $heartbeatPeriod,
        public string $slaveUuid,
        private array $tablesRegex = [],
        private array $databasesRegex = [],
    ) {
    }

    public function validate(): void
    {
        if (!empty($this->host)) {
            $ip = gethostbyname($this->host);
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                throw new ConfigException(ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE);
            }
        }
        if (!empty($this->port) && filter_var(
            $this->port,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        ) === false) {
            throw new ConfigException(ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE);
        }
        if (!empty($this->gtid)) {
            foreach (explode(',', $this->gtid) as $gtid) {
                if (!(bool)preg_match(
                    '/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/',
                    $gtid,
                    $matches
                )) {
                    throw new ConfigException(ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE);
                }
            }
        }
        if (!empty($this->slaveId) && filter_var(
            $this->slaveId,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                ],
            ]
        ) === false) {
            throw new ConfigException(
                ConfigException::SLAVE_ID_ERROR_MESSAGE,
                ConfigException::SLAVE_ID_ERROR_CODE
            );
        }
        if (bccomp($this->binLogPosition, '0') === -1) {
            throw new ConfigException(
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE,
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE
            );
        }
        if (filter_var($this->tableCacheSize, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 0,
            ],
        ]) === false) {
            throw new ConfigException(
                ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE,
                ConfigException::TABLE_CACHE_SIZE_ERROR_CODE
            );
        }
        if ($this->heartbeatPeriod !== 0.0 && false === (
            $this->heartbeatPeriod >= 0.001 && $this->heartbeatPeriod <= 4294967.0
        )) {
            throw new ConfigException(
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE,
                ConfigException::HEARTBEAT_PERIOD_ERROR_CODE
            );
        }
    }


    public function checkDataBasesOnly(string $database): bool
    {
        return ($this->databasesOnly !== [] && !in_array($database, $this->databasesOnly, true))
            || ($this->databasesRegex !== []  && !self::matchNames($database, $this->databasesRegex));
    }


    public function checkTablesOnly(string $table): bool
    {
        return ($this->tablesOnly !== [] && !in_array($table, $this->tablesOnly, true))
            || ($this->tablesRegex !== [] && !self::matchNames($table, $this->tablesRegex));
    }

    private static function matchNames(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    public function checkEvent(int $type): bool
    {
        if ($this->eventsOnly !== [] && !in_array($type, $this->eventsOnly, true)) {
            return false;
        }

        if (in_array($type, $this->eventsIgnore, true)) {
            return false;
        }

        return true;
    }

    public function jsonSerialize(): array
    {
        return get_class_vars(self::class);
    }
}
