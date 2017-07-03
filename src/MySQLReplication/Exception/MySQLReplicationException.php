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
}