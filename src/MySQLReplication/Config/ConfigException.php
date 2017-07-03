<?php

namespace MySQLReplication\Config;

use MySQLReplication\Exception\MySQLReplicationException;

/**
 * Class ConfigException
 * @package MySQLReplication\Config
 */
class ConfigException extends MySQLReplicationException
{
    const USER_ERROR_MESSAGE = 'Incorrect user given';
    const USER_ERROR_CODE = 1;
    const IP_ERROR_MESSAGE = 'Incorrect IP given';
    const IP_ERROR_CODE = 2;
    const PORT_ERROR_MESSAGE = 'Incorrect port given should be numeric ';
    const PORT_ERROR_CODE = 3;
    const PASSWORD_ERROR_MESSAGE = 'Incorrect password type';
    const PASSWORD_ERROR_CODE = 4;
    const DB_NAME_ERROR_MESSAGE = 'Incorrect db name type';
    const DB_NAME_ERROR_CODE = 5;
    const CHARSET_ERROR_MESSAGE = 'Incorrect charset type';
    const CHARSET_ERROR_CODE = 6;
    const GTID_ERROR_MESSAGE = 'Incorrect gtid';
    const GTID_ERROR_CODE = 7;
    const SLAVE_ID_ERROR_MESSAGE = 'Incorrect slave id type';
    const SLAVE_ID_ERROR_CODE = 8;
    const BIN_LOG_FILE_NAME_ERROR_MESSAGE = 'Incorrect binlog name type';
    const BIN_LOG_FILE_NAME_ERROR_CODE = 9;
    const BIN_LOG_FILE_POSITION_ERROR_MESSAGE = 'Incorrect binlog position type';
    const BIN_LOG_FILE_POSITION_ERROR_CODE = 10;
    const MARIADBGTID_ERROR_MESSAGE = 'Maria gtid must be string';
    const MARIADBGTID_ERROR_CODE = 11;
    const TABLE_CACHE_SIZE_ERROR_MESSAGE = 'Table cache must be integer';
    const TABLE_CACHE_SIZE_ERROR_CODE = 12;
}