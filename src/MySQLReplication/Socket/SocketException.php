<?php

declare(strict_types=1);

namespace MySQLReplication\Socket;

use MySQLReplication\Exception\MySQLReplicationException;

class SocketException extends MySQLReplicationException
{
    public const SOCKET_DISCONNECTED_MESSAGE = 'Disconnected by remote side.';
    public const SOCKET_DISCONNECTED_CODE = 100;
    public const SOCKET_UNABLE_TO_WRITE_MESSAGE = 'Unable to write to socket: ';
    public const SOCKET_UNABLE_TO_WRITE_CODE = 101;
    public const SOCKET_UNABLE_TO_CREATE_MESSAGE = 'Unable to create socket: ';
    public const SOCKET_UNABLE_TO_CREATE_CODE = 102;
}
