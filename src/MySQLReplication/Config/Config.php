<?php

namespace MySQLReplication\Config;

/**
 * Class Config
 */
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
     * Config constructor.
     * @param $user
     * @param $host
     * @param $port
     * @param $password
     * @param string $dbName
     * @param string $charset
     */
    public function __construct(
        $user,
        $host,
        $port,
        $password,
        $dbName = '',
        $charset = ''
    ) {
        $this->user = $user;
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->charset = $charset;
    }

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }
}