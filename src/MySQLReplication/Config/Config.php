<?php
declare(strict_types=1);

namespace MySQLReplication\Config;

/**
 * Class Config
 * @package MySQLReplication\Config
 */
class Config implements \JsonSerializable
{
    /**
     * @var string
     */
    private static $user;
    /**
     * @var string
     */
    private static $host;
    /**
     * @var int
     */
    private static $port;
    /**
     * @var string
     */
    private static $password;
    /**
     * @var string
     */
    private static $charset;
    /**
     * @var string
     */
    private static $gtid;
    /**
     * @var int
     */
    private static $slaveId;
    /**
     * @var string
     */
    private static $binLogFileName;
    /**
     * @var int
     */
    private static $binLogPosition;
    /**
     * @var array
     */
    private static $eventsOnly;
    /**
     * @var array
     */
    private static $eventsIgnore;
    /**
     * @var array
     */
    private static $tablesOnly;
    /**
     * @var array
     */
    private static $databasesOnly;
    /**
     * @var string
     */
    private static $mariaDbGtid;
    /**
     * @var int
     */
    private static $tableCacheSize;
    /**
     * @var array
     */
    private static $custom;
    /**
     * @var int
     */
    private static $heartbeatPeriod;

    /**
     * Config constructor.
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $password
     * @param string $charset
     * @param string $gtid
     * @param string $mariaGtid
     * @param int $slaveId
     * @param string $binLogFileName
     * @param $binLogPosition
     * @param array $eventsOnly
     * @param array $eventsIgnore
     * @param array $tablesOnly
     * @param array $databasesOnly
     * @param int $tableCacheSize
     * @param array $custom
     * @param int $heartbeatPeriod
     */
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

    /**
     * @return array
     */
    public static function getCustom(): array
    {
        return self::$custom;
    }

    /**
     * @return string
     */
    public static function getUser(): string
    {
        return self::$user;
    }

    /**
     * @return string
     */
    public static function getHost(): string
    {
        return self::$host;
    }

    /**
     * @return int
     */
    public static function getPort(): int
    {
        return self::$port;
    }

    /**
     * @return string
     */
    public static function getPassword(): string
    {
        return self::$password;
    }

    /**
     * @return string
     */
    public static function getCharset(): string
    {
        return self::$charset;
    }

    /**
     * @return string
     */
    public static function getGtid(): string
    {
        return self::$gtid;
    }

    /**
     * @return string
     */
    public static function getMariaDbGtid(): string
    {
        return self::$mariaDbGtid;
    }

    /**
     * @return int
     */
    public static function getSlaveId(): int
    {
        return self::$slaveId;
    }

    /**
     * @return string
     */
    public static function getBinLogFileName(): string
    {
        return self::$binLogFileName;
    }

    /**
     * @return int
     */
    public static function getBinLogPosition(): int
    {
        return self::$binLogPosition;
    }

    /**
     * @return array
     */
    public static function getEventsOnly(): array
    {
        return self::$eventsOnly;
    }

    /**
     * @return array
     */
    public static function getEventsIgnore(): array
    {
        return self::$eventsIgnore;
    }

    /**
     * @return array
     */
    public static function getTablesOnly(): array
    {
        return self::$tablesOnly;
    }

    /**
     * @return array
     */
    public static function getDatabasesOnly(): array
    {
        return self::$databasesOnly;
    }

    /**
     * @return int
     */
    public static function getTableCacheSize(): int
    {
        return self::$tableCacheSize;
    }

    /**
     * @param string $database
     * @return bool
     */
    public static function checkDataBasesOnly(string $database): bool
    {
        return [] !== self::getDatabasesOnly() && !\in_array($database, self::getDatabasesOnly(), true);
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function checkTablesOnly(string $table): bool
    {
        return [] !== self::getTablesOnly() && !\in_array($table, self::getTablesOnly(), true);
    }

    /**
     * @param int $type
     * @return bool
     */
    public static function checkEvent(int $type): bool
    {
        if ([] !== self::getEventsOnly() && !\in_array($type, self::getEventsOnly(), true)) {
            return false;
        }

        if (\in_array($type, self::getEventsIgnore(), true)) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public static function getHeartbeatPeriod(): int
    {
        return self::$heartbeatPeriod;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_class_vars(self::class);
    }
}