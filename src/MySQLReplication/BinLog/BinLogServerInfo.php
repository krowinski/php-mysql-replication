<?php

declare(strict_types=1);

namespace MySQLReplication\BinLog;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

class BinLogServerInfo
{
    private const MYSQL_VERSION_MARIADB = 'MariaDB';
    private const MYSQL_VERSION_PERCONA = 'Percona';
    private const MYSQL_VERSION_GENERIC = 'MySQL';

    public function __construct(
        public int $protocolVersion,
        public string $serverVersion,
        public int $connectionId,
        public string $salt,
        public BinLogAuthPluginMode $authPlugin,
        public string $versionName,
        public float $versionRevision
    ) {
    }

    public static function make(string $data, string $version): self
    {
        $i = 0;
        $length = strlen($data);
        $protocolVersion = ord($data[$i]);
        ++$i;

        //version
        $serverVersion = '';
        $start = $i;
        for ($i = $start; $i < $length; ++$i) {
            if ($data[$i] === chr(0)) {
                ++$i;
                break;
            }
            $serverVersion .= $data[$i];
        }

        //connection_id 4 bytes
        $connectionId = BinaryDataReader::unpack('I', $data[$i] . $data[++$i] . $data[++$i] . $data[++$i])[1];
        ++$i;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        $salt = '';
        for ($j = $i; $j < $i + 8; ++$j) {
            $salt .= $data[$j];
        }
        $i += 8;

        //filler_1 (1) -- 0x00
        ++$i;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i += 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        $characterSet = $data[$i];
        ++$i;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i += 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i += 2;

        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $saltLen = ord($data[$i]);
        ++$i;

        $saltLen = max(12, $saltLen - 9);

        $i += 10;

        //next salt
        if ($length >= $i + $saltLen) {
            for ($j = $i; $j < $i + $saltLen; ++$j) {
                $salt .= $data[$j];
            }
        }
        $authPlugin = '';
        $i += $saltLen + 1;
        for ($j = $i; $j < $length - 1; ++$j) {
            $authPlugin .= $data[$j];
        }

        return new self(
            $protocolVersion,
            $serverVersion,
            $connectionId,
            $salt,
            BinLogAuthPluginMode::make($authPlugin),
            self::parseVersion($serverVersion),
            self::parseRevision($version)
        );
    }

    public function isMariaDb(): bool
    {
        return $this->versionName === self::MYSQL_VERSION_MARIADB;
    }

    public function isPercona(): bool
    {
        return $this->versionName === self::MYSQL_VERSION_PERCONA;
    }

    public function isGeneric(): bool
    {
        return $this->versionName === self::MYSQL_VERSION_GENERIC;
    }

    /**
     * @see http://stackoverflow.com/questions/37317869/determine-if-mysql-or-percona-or-mariadb
     */
    private static function parseVersion(string $version): string
    {
        if ($version !== '') {
            if (str_contains($version, self::MYSQL_VERSION_MARIADB)) {
                return self::MYSQL_VERSION_MARIADB;
            }
            if (str_contains($version, self::MYSQL_VERSION_PERCONA)) {
                return self::MYSQL_VERSION_PERCONA;
            }
        }

        return self::MYSQL_VERSION_GENERIC;
    }

    private static function parseRevision(string $version): float
    {
        return (float)$version;
    }
}
