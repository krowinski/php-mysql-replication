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
        $user,
        $host,
        $port,
        $password,
        $charset,
        $gtid,
        $mariaGtid,
        $slaveId,
        $binLogFileName,
        $binLogPosition,
        array $eventsOnly,
        array $eventsIgnore,
        array $tablesOnly,
        array $databasesOnly,
        $tableCacheSize,
        array $custom,
        $heartbeatPeriod
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
    public static function validate()
    {
        if (!empty(self::$user) && !is_string(self::$user)) {
            throw new ConfigException(ConfigException::USER_ERROR_MESSAGE, ConfigException::USER_ERROR_CODE);
        }
        if (!empty(self::$host)) {
            $ip = gethostbyname(self::$host);
            if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
                throw new ConfigException(ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE);
            }
        }
        if (!empty(self::$port) && false === filter_var(
                self::$port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]
            )
        ) {
            throw new ConfigException(ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE);
        }
        if (!empty(self::$password) && !is_string(self::$password) && !is_numeric(self::$password)) {
            throw new ConfigException(ConfigException::PASSWORD_ERROR_MESSAGE, ConfigException::PASSWORD_ERROR_CODE);
        }
        if (!empty(self::$charset) && !is_string(self::$charset)) {
            throw new ConfigException(ConfigException::CHARSET_ERROR_MESSAGE, ConfigException::CHARSET_ERROR_CODE);
        }
        if (!empty(self::$gtid) && !is_string(self::$gtid)) {
            foreach (explode(',', self::$gtid) as $gtid) {
                if (!(bool)preg_match(
                    '/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches
                )
                ) {
                    throw new ConfigException(ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE);
                }
            }
        }
        if (!empty(self::$slaveId) && false === filter_var(self::$slaveId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])
        ) {
            throw new ConfigException(ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE);
        }
        if (!empty(self::$binLogFileName) && !is_string(self::$binLogFileName)) {
            throw new ConfigException(
                ConfigException::BIN_LOG_FILE_NAME_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_NAME_ERROR_CODE
            );
        }
        if (false === filter_var(self::$binLogPosition, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE
            );
        }

        if (!empty(self::$mariaDbGtid) && !is_string(self::$mariaDbGtid)) {
            throw new ConfigException(
                ConfigException::MARIADBGTID_ERROR_MESSAGE, ConfigException::MARIADBGTID_ERROR_CODE
            );
        }
        if (false === filter_var(self::$tableCacheSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
            throw new ConfigException(
                ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE
            );
        }
        if (0 !== self::$heartbeatPeriod && false === filter_var(
                self::$heartbeatPeriod, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967]]
            )
        ) {
            throw new ConfigException(
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE
            );
        }
    }

    /**
     * @return array
     */
    public static function getCustom()
    {
        return self::$custom;
    }

    /**
     * @return string
     */
    public static function getUser()
    {
        return self::$user;
    }

    /**
     * @return string
     */
    public static function getHost()
    {
        return self::$host;
    }

    /**
     * @return int
     */
    public static function getPort()
    {
        return self::$port;
    }

    /**
     * @return string
     */
    public static function getPassword()
    {
        return self::$password;
    }

    /**
     * @return string
     */
    public static function getCharset()
    {
        return self::$charset;
    }

    /**
     * @return string
     */
    public static function getGtid()
    {
        return self::$gtid;
    }

    /**
     * @return string
     */
    public static function getMariaDbGtid()
    {
        return self::$mariaDbGtid;
    }

    /**
     * @return int
     */
    public static function getSlaveId()
    {
        return self::$slaveId;
    }

    /**
     * @return string
     */
    public static function getBinLogFileName()
    {
        return self::$binLogFileName;
    }

    /**
     * @return int
     */
    public static function getBinLogPosition()
    {
        return self::$binLogPosition;
    }

    /**
     * @return array
     */
    public static function getEventsOnly()
    {
        return self::$eventsOnly;
    }

    /**
     * @return array
     */
    public static function getEventsIgnore()
    {
        return self::$eventsIgnore;
    }

    /**
     * @return array
     */
    public static function getTablesOnly()
    {
        return self::$tablesOnly;
    }

    /**
     * @return array
     */
    public static function getDatabasesOnly()
    {
        return self::$databasesOnly;
    }

    /**
     * @return int
     */
    public static function getTableCacheSize()
    {
        return self::$tableCacheSize;
    }

    /**
     * @param string $database
     * @return bool
     */
    public static function checkDataBasesOnly($database)
    {
        return [] !== Config::getDatabasesOnly() && !in_array($database, Config::getDatabasesOnly(), true);
    }

    /**
     * @param string $table
     * @return bool
     */
    public static function checkTablesOnly($table)
    {
        return [] !== Config::getTablesOnly() && !in_array($table, Config::getTablesOnly(), true);
    }

    /**
     * @param int $type
     * @return bool
     */
    public static function checkEvent($type)
    {
        if ([] !== Config::getEventsOnly() && !in_array($type, Config::getEventsOnly(), true)) {
            return false;
        }

        if (in_array($type, Config::getEventsIgnore(), true)) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public static function getHeartbeatPeriod()
    {
        return self::$heartbeatPeriod;
    }
}