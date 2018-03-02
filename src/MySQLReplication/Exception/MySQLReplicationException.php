<?php

namespace MySQLReplication\Exception;

/**
 * Class MySQLReplicationException
 * @package MySQLReplication\Exception
 */
class MySQLReplicationException extends \Exception
{
    const SOCKET_DISCONNECTED_MESSAGE = 'Disconnected by remote side.';
    const SOCKET_DISCONNECTED_CODE = 100;
    const SOCKET_UNABLE_TO_WRITE_MESSAGE = 'Unable to write to socket: ';
    const SOCKET_UNABLE_TO_WRITE_CODE= 101;
    const SOCKET_UNABLE_TO_CREATE_MESSAGE = 'Unable to create socket: ';
    const SOCKET_UNABLE_TO_CREATE_CODE= 102;

    const INCORRECT_GTID_MESSAGE = 'Incorrect gtid';
    const INCORRECT_GTID_CODE = 200;

    const UNKNOWN_JSON_TYPE_MESSAGE = 'Unknown JSON type: ';
    const UNKNOWN_JSON_TYPE_CODE = 300;

    const USER_ERROR_MESSAGE = 'Incorrect user given';
    const USER_ERROR_CODE = 400;
    const IP_ERROR_MESSAGE = 'Incorrect IP given';
    const IP_ERROR_CODE = 401;
    const PORT_ERROR_MESSAGE = 'Incorrect port given should be numeric ';
    const PORT_ERROR_CODE = 402;
    const PASSWORD_ERROR_MESSAGE = 'Incorrect password type';
    const PASSWORD_ERROR_CODE = 403;
    const DB_NAME_ERROR_MESSAGE = 'Incorrect db name type';
    const DB_NAME_ERROR_CODE = 404;
    const CHARSET_ERROR_MESSAGE = 'Incorrect charset type';
    const CHARSET_ERROR_CODE = 405;
    const GTID_ERROR_MESSAGE = 'Incorrect gtid';
    const GTID_ERROR_CODE = 406;
    const SLAVE_ID_ERROR_MESSAGE = 'Incorrect slave id type';
    const SLAVE_ID_ERROR_CODE = 407;
    const BIN_LOG_FILE_NAME_ERROR_MESSAGE = 'Incorrect binlog name';
    const BIN_LOG_FILE_NAME_ERROR_CODE = 408;
    const BIN_LOG_FILE_POSITION_ERROR_MESSAGE = 'Incorrect binlog position';
    const BIN_LOG_FILE_POSITION_ERROR_CODE = 409;
    const MARIADBGTID_ERROR_MESSAGE = 'Maria gtid must be string';
    const MARIADBGTID_ERROR_CODE = 410;
    const TABLE_CACHE_SIZE_ERROR_MESSAGE = 'Table cache must be integer';
    const TABLE_CACHE_SIZE_ERROR_CODE = 411;
    const HEARTBEAT_PERIOD_ERROR_MESSAGE = 'Heartbeat period must be integer min:1 max:4294967';
    const HEARTBEAT_PERIOD_ERROR_CODE = 412;

    const BINLOG_NOT_ENABLED = 'MySQL binary logging is not enabled.';
    const BINLOG_NOT_ENABLED_CODE = 413;
}