<?php

declare(strict_types=1);

namespace MySQLReplication\Config;

use MySQLReplication\Exception\MySQLReplicationException;

class ConfigException extends MySQLReplicationException
{
    public const TABLE_CACHE_SIZE_ERROR_MESSAGE = 'Table cache must be integer';
    public const TABLE_CACHE_SIZE_ERROR_CODE = 411;
    public const HEARTBEAT_PERIOD_ERROR_MESSAGE = 'Heartbeat period must be integer min:1 max:4294967';
    public const HEARTBEAT_PERIOD_ERROR_CODE = 412;
    public const BIN_LOG_FILE_POSITION_ERROR_MESSAGE = 'Incorrect binlog position';
    public const BIN_LOG_FILE_POSITION_ERROR_CODE = 409;
    public const SLAVE_ID_ERROR_MESSAGE = 'Incorrect slave id type';
    public const SLAVE_ID_ERROR_CODE = 407;
    public const GTID_ERROR_MESSAGE = 'Incorrect gtid';
    public const GTID_ERROR_CODE = 406;
    public const PORT_ERROR_MESSAGE = 'Incorrect port given should be numeric ';
    public const PORT_ERROR_CODE = 402;
    public const IP_ERROR_MESSAGE = 'Incorrect IP given';
    public const IP_ERROR_CODE = 401;
}
