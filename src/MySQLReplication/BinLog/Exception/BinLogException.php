<?php

namespace MySQLReplication\BinLog\Exception;

use MySQLReplication\Exception\MySQLReplicationException;

/**
 * Class BinLogException
 * @package MySQLReplication\BinLog\Exception
 */
class BinLogException extends MySQLReplicationException
{
    const DISCONNECTED_MESSAGE = 'Disconnected by remote side';
    const UNABLE_TO_WRITE_SOCKET = 'Unable to write to socket: ';
    const UNABLE_TO_CREATE_SOCKET = 'Unable to create socket: ';

}