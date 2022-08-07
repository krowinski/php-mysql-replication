<?php
declare(strict_types=1);

namespace MySQLReplication\BinLog;

class BinLogServerInfo
{
    private const MYSQL_VERSION_MARIADB = 'MariaDB';
    private const MYSQL_VERSION_PERCONA = 'Percona';
    private const MYSQL_VERSION_GENERIC = 'MySQL';
    /**
     * @var array
     */
    private $serverInfo;

    public static function parsePackage(string $data, string $version): BinLogServerInfo
    {
        $i = 0;
        $length = strlen($data);
        $serverInfo['protocol_version'] = ord($data[$i]);
        ++$i;

        //version
        $serverInfo['server_version'] = '';
        $start = $i;
        for ($i = $start; $i < $length; ++$i) {
            if ($data[$i] === chr(0)) {
                ++$i;
                break;
            }
            $serverInfo['server_version'] .= $data[$i];
        }

        //connection_id 4 bytes
        $serverInfo['connection_id'] = unpack('I', $data[$i] . $data[++$i] . $data[++$i] . $data[++$i])[1];
        ++$i;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        $serverInfo['salt'] = '';
        for ($j = $i; $j < $i + 8; ++$j) {
            $serverInfo['salt'] .= $data[$j];
        }
        $i += 8;

        //filler_1 (1) -- 0x00
        ++$i;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i += 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        $serverInfo['character_set'] = $data[$i];
        ++$i;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i += 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i += 2;

        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($data[$i]);
        ++$i;

        $salt_len = max(12, $salt_len - 9);

        $i += 10;

        //next salt
        if ($length >= $i + $salt_len) {
            for ($j = $i; $j < $i + $salt_len; ++$j) {
                $serverInfo['salt'] .= $data[$j];
            }

        }
        $serverInfo['auth_plugin_name'] = '';
        $i += $salt_len + 1;
        for ($j = $i; $j < $length - 1; ++$j) {
            $serverInfo['auth_plugin_name'] .= $data[$j];
        }

        $serverInfo['version_name'] = self::parseVersion($version);
        $serverInfo['version_revision'] = self::parseRevision($version);

        return new self($serverInfo);
    }

    public function __construct(array $serverInfo)
    {
        $this->serverInfo = $serverInfo;
    }

    public function getSalt(): string
    {
        return $this->serverInfo['salt'];
    }

    /**
     * @see http://stackoverflow.com/questions/37317869/determine-if-mysql-or-percona-or-mariadb
     */
    private static function parseVersion(string $version): string
    {
        if ('' !== $version) {
            if (false !== strpos($version, self::MYSQL_VERSION_MARIADB)) {
                return self::MYSQL_VERSION_MARIADB;
            }
            if (false !== strpos($version, self::MYSQL_VERSION_PERCONA)) {
                return self::MYSQL_VERSION_PERCONA;
            }
        }

        return self::MYSQL_VERSION_GENERIC;
    }

    public function getRevision(): float
    {
        return $this->serverInfo['version_revision'];
    }

    public function getVersion(): string
    {
        return $this->serverInfo['version_name'];
    }

    public function isMariaDb(): bool
    {
        return self::MYSQL_VERSION_MARIADB === $this->getVersion();
    }

    public function isPercona(): bool
    {
        return self::MYSQL_VERSION_PERCONA === $this->getVersion();
    }

    public function isGeneric(): bool
    {
        return self::MYSQL_VERSION_GENERIC === $this->getVersion();
    }

    private static function parseRevision(string $version): float
    {
        return (float)$version;
    }
}