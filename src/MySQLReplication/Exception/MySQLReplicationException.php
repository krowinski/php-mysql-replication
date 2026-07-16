<?php

declare(strict_types=1);

namespace MySQLReplication\Exception;

use Exception;

class MySQLReplicationException extends Exception
{
    public const BINLOG_NOT_ENABLED = 'MySQL binary logging is not enabled.';
    public const BINLOG_NOT_ENABLED_CODE = 413;

    public const BINLOG_AUTH_NOT_SUPPORTED = 'MySQL auth plugin is not supported.';
    public const BINLOG_AUTH_NOT_SUPPORTED_CODE = 414;

    public const BINLOG_FORMAT_NOT_ROW = 'MySQL binlog_format must be ROW for row-based replication.';
    public const BINLOG_FORMAT_NOT_ROW_CODE = 415;
}
