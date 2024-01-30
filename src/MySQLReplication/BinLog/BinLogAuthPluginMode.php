<?php

declare(strict_types=1);

namespace MySQLReplication\BinLog;

enum BinLogAuthPluginMode: string
{
    case MysqlNativePassword = 'mysql_native_password';
    case CachingSha2Password = 'caching_sha2_password';
}
