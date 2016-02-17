<?php

namespace MySQLReplication\Pack;

/**
 * Class ServerInfo
 * @package MySQLReplication\Pack
 */
class ServerInfo
{
    /**
     * @var array
     */
    public static $INFO = [];

    /**
     * @param $pack
     */
    public static function run($pack)
    {
        $i = 0;
        $length = strlen($pack);
        self::$INFO['protocol_version'] = ord($pack[$i]);
        $i++;

        //version
        self::$INFO['server_version'] = '';
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
                self::$INFO['server_version'] .= $pack[$i];
            }
        }

        //connection_id 4 bytes
        self::$INFO['connection_id'] = $pack[$i] . $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        self::$INFO['salt'] = '';
        for ($j = $i; $j < $i + 8; $j++)
        {
            self::$INFO['salt'] .= $pack[$j];
        }
        $i = $i + 8;

        //filler_1 (1) -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i = $i + 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        self::$INFO['character_set'] = $pack[$i];
        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i = $i + 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i = $i + 2;

        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($pack[$i]);
        $i++;

        $salt_len = max(12, $salt_len - 9);

        $i = $i + 10;

        //next salt
        if ($length >= $i + $salt_len)
        {
            for ($j = $i; $j < $i + $salt_len; $j++)
            {
                self::$INFO['salt'] .= $pack[$j];
            }

        }
        self::$INFO['auth_plugin_name'] = '';
        $i += $salt_len + 1;
        for ($j = $i; $j < $length - 1; $j++)
        {
            self::$INFO['auth_plugin_name'] .= $pack[$j];
        }
    }

    /**
     * @return mixed
     */
    public static function getSalt()
    {
        return self::$INFO['salt'];
    }
}