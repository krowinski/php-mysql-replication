<?php

namespace MySQLReplication\BinLog;

/**
 * Class BinLogServerInfo
 * @package MySQLReplication\BinLog
 */
class BinLogServerInfo
{
    const MYSQL_VERSION_MARIADB = 'MariaDB';
    const MYSQL_VERSION_PERCONA = 'Percona';
    const MYSQL_VERSION_GENERIC = 'MySQL';
    /**
     * @var array
     */
    private static $serverInfo = [];

    /**
     * @param $pack
     */
    public static function parsePackage($pack)
    {
        $i = 0;
        $length = strlen($pack);
        self::$serverInfo['protocol_version'] = ord($pack[$i]);
        $i++;

        //version
        self::$serverInfo['server_version'] = '';
        $start = $i;
        for ($i = $start; $i < $length; $i++)
        {
            if ($pack[$i] === chr(0))
            {
                $i++;
                break;
            }
            else
            {
                self::$serverInfo['server_version'] .= $pack[$i];
            }
        }

        //connection_id 4 bytes
        self::$serverInfo['connection_id'] = $pack[$i] . $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        self::$serverInfo['salt'] = '';
        for ($j = $i; $j < $i + 8; $j++)
        {
            self::$serverInfo['salt'] .= $pack[$j];
        }
        $i += 8;

        //filler_1 (1) -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i += 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        self::$serverInfo['character_set'] = $pack[$i];
        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i += 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i += 2;

        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($pack[$i]);
        $i++;

        $salt_len = max(12, $salt_len - 9);

        $i += 10;

        //next salt
        if ($length >= $i + $salt_len)
        {
            for ($j = $i; $j < $i + $salt_len; $j++)
            {
                self::$serverInfo['salt'] .= $pack[$j];
            }

        }
        self::$serverInfo['auth_plugin_name'] = '';
        $i += $salt_len + 1;
        for ($j = $i; $j < $length - 1; $j++)
        {
            self::$serverInfo['auth_plugin_name'] .= $pack[$j];
        }
        self::$serverInfo['version_name'] = self::MYSQL_VERSION_GENERIC;
    }

    /**
     * @return mixed
     */
    public static function getSalt()
    {
        return self::$serverInfo['salt'];
    }

    /**
     * @see http://stackoverflow.com/questions/37317869/determine-if-mysql-or-percona-or-mariadb
     * @param string $version
     */
    public static function parseVersion($version)
    {
        if ('' !== $version)
        {
            if (false !== strpos($version, self::MYSQL_VERSION_MARIADB))
            {
                self::$serverInfo['version_name'] = self::MYSQL_VERSION_MARIADB;
            }
            else if (false !== strpos($version, self::MYSQL_VERSION_PERCONA))
            {
                self::$serverInfo['version_name'] = self::MYSQL_VERSION_PERCONA;
            }
        }
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return self::$serverInfo['version_name'];
    }
}