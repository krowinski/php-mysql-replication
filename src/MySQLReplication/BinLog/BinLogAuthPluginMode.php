<?php

declare(strict_types=1);

namespace MySQLReplication\BinLog;

use MySQLReplication\Exception\MySQLReplicationException;

enum BinLogAuthPluginMode: string
{
    case MysqlNativePassword = 'mysql_native_password';
    case CachingSha2Password = 'caching_sha2_password';

    public static function make(string $authPluginName): self
    {
        $authPlugin = self::tryFrom($authPluginName);
        if ($authPlugin === null) {
            throw new MySQLReplicationException(
                MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED,
                MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED_CODE
            );
        }

        return $authPlugin;
    }
}
