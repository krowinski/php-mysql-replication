<?php

namespace MySQLReplication\Config;

use MySQLReplication\Config\Exception\ConfigException;

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
     * @var string
     */
    private $mariaDbGtid;
    /**
     * @var int
     */
    private $tableCacheSize;

    /**
     * Config constructor.
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $password
     * @param string $dbName
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
     */
    public function __construct(
        $user,
        $host,
        $port,
        $password,
        $dbName,
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
        $tableCacheSize
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
        $this->mariaDbGtid = $mariaGtid;
        $this->tableCacheSize = $tableCacheSize;
    }

    /**
     * @throws ConfigException
     */
    public function validate()
    {
        if (!empty($this->user) && false === is_string($this->user))
        {
            throw new ConfigException(ConfigException::USER_ERROR_MESSAGE, ConfigException::USER_ERROR_CODE);
        }
        if (!empty($this->host))
        {
            $ip = gethostbyname($this->host);
            if (false === filter_var($ip, FILTER_VALIDATE_IP))
            {
                throw new ConfigException(ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE);
            }
        }
        if (!empty($this->port) && false === filter_var($this->port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))
        {
            throw new ConfigException(ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE);
        }
        if (!empty($this->password) && false === is_string($this->password) && false === is_numeric($this->password))
        {
            throw new ConfigException(ConfigException::PASSWORD_ERROR_MESSAGE, ConfigException::PASSWORD_ERROR_CODE);
        }
        if (!empty($this->dbName) && false === is_string($this->dbName))
        {
            throw new ConfigException(ConfigException::DB_NAME_ERROR_MESSAGE, ConfigException::DB_NAME_ERROR_CODE);
        }
        if (!empty($this->charset) && false === is_string($this->charset))
        {
            throw new ConfigException(ConfigException::CHARSET_ERROR_MESSAGE, ConfigException::CHARSET_ERROR_CODE);
        }
        if (!empty($this->gtid) && false === is_string($this->gtid))
        {
            foreach (explode(',', $this->gtid) as $gtid)
            {
                if (false === (bool)preg_match('/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches))
                {
                    throw new ConfigException(ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE);
                }
            }
        }
        if (!empty($this->slaveId) && false === filter_var($this->slaveId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))
        {
            throw new ConfigException(ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE);
        }
        if (!empty($this->binLogFileName) && false === is_string($this->binLogFileName))
        {
            throw new ConfigException(ConfigException::BIN_LOG_FILE_NAME_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_NAME_ERROR_CODE);
        }
        if (!empty($this->binLogPosition) && false === filter_var($this->binLogPosition, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))
        {
            throw new ConfigException(ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE);
        }
        if (!empty($this->mariaDbGtid) && false === is_string($this->mariaDbGtid))
        {
            throw new ConfigException(ConfigException::MARIADBGTID_ERROR_MESSAGE, ConfigException::MARIADBGTID_ERROR_CODE);
        }
        if (false === filter_var($this->tableCacheSize, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))
        {
            throw new ConfigException(ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE);
        }
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
     * @return string
     */
    public function getMariaDbGtid()
    {
        return $this->mariaDbGtid;
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

    /**
     * @return int
     */
    public function getTableCacheSize()
    {
        return $this->tableCacheSize;
    }
}