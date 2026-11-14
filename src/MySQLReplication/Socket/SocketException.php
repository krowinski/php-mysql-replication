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
    // Reasons of this class use codes above 1000, so they never collide with a native errno (also used as a code).
    public const SOCKET_READ_TIMEOUT_MESSAGE = 'No data from the replication source for %s s (read %d of %d bytes).';
    public const SOCKET_READ_TIMEOUT_CODE = 1001;
    public const SOCKET_WRITE_TIMEOUT_MESSAGE = 'Wrote %d of %d bytes before the write timeout of %s s.';
    public const SOCKET_WRITE_TIMEOUT_CODE = 1002;
    public const SOCKET_CONNECT_TIMEOUT_MESSAGE = 'Unable to connect within the timeout of %s s (errno %d).';
    public const SOCKET_CONNECT_TIMEOUT_CODE = 1003;
    public const SOCKET_UNABLE_TO_SET_TIMEOUT_MESSAGE = 'Unable to set the socket timeout: ';
    public const SOCKET_UNABLE_TO_SET_TIMEOUT_CODE = 1004;
}
