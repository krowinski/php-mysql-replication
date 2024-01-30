<?php

declare(strict_types=1);

namespace MySQLReplication\Socket;

use Socket as NativeSocket;

class Socket implements SocketInterface
{
    private NativeSocket $socket;

    public function __destruct()
    {
        socket_shutdown($this->socket);
        socket_close($this->socket);
    }

    public function connectToStream(string $host, int $port): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new SocketException(
                SocketException::SOCKET_UNABLE_TO_CREATE_MESSAGE . $this->getSocketErrorMessage(),
                SocketException::SOCKET_UNABLE_TO_CREATE_CODE
            );
        }
        $this->socket = $socket;
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);

        if (!socket_connect($this->socket, $host, $port)) {
            throw new SocketException($this->getSocketErrorMessage(), $this->getSocketErrorCode());
        }
    }

    public function readFromSocket(int $length): string
    {
        $received = socket_recv($this->socket, $buf, $length, MSG_WAITALL);
        if ($length === $received) {
            return $buf;
        }

        // http://php.net/manual/en/function.socket-recv.php#47182
        if ($received === 0) {
            throw new SocketException(
                SocketException::SOCKET_DISCONNECTED_MESSAGE,
                SocketException::SOCKET_DISCONNECTED_CODE
            );
        }

        throw new SocketException($this->getSocketErrorMessage(), $this->getSocketErrorCode());
    }

    public function writeToSocket(string $data): void
    {
        if (!socket_write($this->socket, $data, strlen($data))) {
            throw new SocketException(
                SocketException::SOCKET_UNABLE_TO_WRITE_MESSAGE . $this->getSocketErrorMessage(),
                SocketException::SOCKET_UNABLE_TO_WRITE_CODE
            );
        }
    }

    private function getSocketErrorMessage(): string
    {
        return socket_strerror($this->getSocketErrorCode());
    }

    private function getSocketErrorCode(): int
    {
        return socket_last_error();
    }
}
