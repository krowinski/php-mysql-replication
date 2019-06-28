<?php
declare(strict_types=1);

namespace MySQLReplication\Config;

use JsonSerializable;

class Config implements JsonSerializable
{
    private static $user;
    private static $host;
    private static $port;
    private static $password;
    private static $charset;
    private static $gtid;
    private static $slaveId;
    private static $binLogFileName;
    private static $binLogPosition;
    private static $eventsOnly;
    private static $eventsIgnore;
    private static $tablesOnly;
    private static $databasesOnly;
    private static $mariaDbGtid;
    private static $tableCacheSize;
    private static $custom;
    private static $heartbeatPeriod;

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
        int $heartbeatPeriod
    ) {
        self::$user = $user;
        self::$host = $host;
        self::$port = $port;
        self::$password = $password;
        self::$charset = $charset;
        self::$gtid = $gtid;
        self::$slaveId = $slaveId;
        self::$binLogFileName = $binLogFileName;
        self::$binLogPosition = $binLogPosition;
        self::$eventsOnly = $eventsOnly;
        self::$eventsIgnore = $eventsIgnore;
        self::$tablesOnly = $tablesOnly;
        self::$databasesOnly = $databasesOnly;
        self::$mariaDbGtid = $mariaGtid;
        self::$tableCacheSize = $tableCacheSize;
        self::$custom = $custom;
        self::$heartbeatPeriod = $heartbeatPeriod;
    }

    public static function makeConfigFromArray(array $config): Config
    {
        $configBuilder = new ConfigBuilder();
        foreach ($config as $k => $v) {
            if ('user' === $k) {
                $configBuilder->withUser($v);
            }
            if ('ip' === $k || 'host' === $k) {
                $configBuilder->withHost($v);
            }
            if ('port' === $k) {
                $configBuilder->withPort($v);
            }
            if ('password' === $k) {
                $configBuilder->withPassword($v);
            }
            if ('charset' === $k) {
                $configBuilder->withCharset($v);
            }
            if ('gtid' === $k) {
                $configBuilder->withGtid($v);
            }
            if ('slaveId' === $k) {
                $configBuilder->withSlaveId($v);
            }
            if ('binLogFileName' === $k) {
                $configBuilder->withBinLogFileName($v);
            }
            if ('binLogPosition' === $k) {
                $configBuilder->withBinLogPosition($v);
            }
            if ('eventsOnly' === $k) {
                $configBuilder->withEventsOnly($v);
            }
            if ('eventsIgnore' === $k) {
                $configBuilder->withEventsIgnore($v);
            }
            if ('tablesOnly' === $k) {
                $configBuilder->withTablesOnly($v);
            }
            if ('databasesOnly' === $k) {
                $configBuilder->withDatabasesOnly($v);
            }
            if ('mariaDbGtid' === $k) {
                $configBuilder->withMariaDbGtid($v);
            }
            if ('tableCacheSize' === $k) {
                $configBuilder->withTableCacheSize($v);
            }
            if ('custom' === $k) {
                $configBuilder->withCustom($v);
            }
            if ('heartbeatPeriod' === $k) {
                $configBuilder->withHeartbeatPeriod($v);
            }
        }

        return $configBuilder->build();
    }

    /**
     * @throws ConfigException
     */
    public static function validate(): void
    {
        if (!empty(self::$host)) {
            $ip = gethostbyname(self::$host);
            if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new ConfigException(ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE);
            }
        }
        if (!empty(self::$port) && false === filter_var(
                self::$port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]
            )) {
            throw new ConfigException(ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE);
        }
        if (!empty(self::$gtid)) {
            foreach (explode(',', self::$gtid) as $gtid) {
                if (!(bool)preg_match(
                    '/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches
                )) {
                    throw new ConfigException(ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE);
                }
            }
        }
        if (!empty(self::$slaveId) && false === filter_var(
                self::$slaveId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]
            )) {
            throw new ConfigException(ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE);
        }
        if (false === filter_var(self::$binLogPosition, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE
            );
        }
        if (false === filter_var(self::$tableCacheSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE
            );
        }
        if (0 !== self::$heartbeatPeriod && false === filter_var(
                self::$heartbeatPeriod, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967]]
            )) {
            throw new ConfigException(
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE
            );
        }
    }

    public static function getCustom(): array
    {
        return self::$custom;
    }

    public static function getUser(): string
    {
        return self::$user;
    }

    public static function getHost(): string
    {
        return self::$host;
    }

    public static function getPort(): int
    {
        return self::$port;
    }

    public static function getPassword(): string
    {
        return self::$password;
    }

    public static function getCharset(): string
    {
        return self::$charset;
    }

    public static function getGtid(): string
    {
        return self::$gtid;
    }

    public static function getMariaDbGtid(): string
    {
        return self::$mariaDbGtid;
    }

    public static function getSlaveId(): int
    {
        return self::$slaveId;
    }

    public static function getBinLogFileName(): string
    {
        return self::$binLogFileName;
    }

    public static function getBinLogPosition(): int
    {
        return self::$binLogPosition;
    }

    public static function getTableCacheSize(): int
    {
        return self::$tableCacheSize;
    }

    public static function checkDataBasesOnly(string $database): bool
    {
        return [] !== self::getDatabasesOnly() && !in_array($database, self::getDatabasesOnly(), true);
    }

    public static function getDatabasesOnly(): array
    {
        return self::$databasesOnly;
    }

    public static function checkTablesOnly(string $table): bool
    {
        return [] !== self::getTablesOnly() && !in_array($table, self::getTablesOnly(), true);
    }

    public static function getTablesOnly(): array
    {
        return self::$tablesOnly;
    }

    public static function checkEvent(int $type): bool
    {
        if ([] !== self::getEventsOnly() && !in_array($type, self::getEventsOnly(), true)) {
            return false;
        }

        if (in_array($type, self::getEventsIgnore(), true)) {
            return false;
        }

        return true;
    }

    public static function getEventsOnly(): array
    {
        return self::$eventsOnly;
    }

    public static function getEventsIgnore(): array
    {
        return self::$eventsIgnore;
    }

    public static function getHeartbeatPeriod(): int
    {
        return self::$heartbeatPeriod;
    }

    public function jsonSerialize()
    {
        return get_class_vars(self::class);
    }
}