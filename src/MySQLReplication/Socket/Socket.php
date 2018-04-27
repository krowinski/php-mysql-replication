<?php


namespace MySQLReplication\Socket;

/**
 * Class Socket
 * @package MySQLReplication\Socket
 */
class Socket implements SocketInterface
{
    /**
     * @var resource
     */
    private $socket;

    public function __destruct()
    {
        if ($this->isConnected()) {
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return is_resource($this->socket);
    }

    /**
     * @param string $host
     * @param int $port
     * @throws SocketException
     */
    public function connectToStream($host, $port)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            throw new SocketException(
                SocketException::SOCKET_UNABLE_TO_CREATE_MESSAGE . $this->getSocketErrorMessage(),
                SocketException::SOCKET_UNABLE_TO_CREATE_CODE
            );
        }
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);

        if (!socket_connect($this->socket, $host, $port)) {
            throw new SocketException($this->getSocketErrorMessage(), $this->getSocketErrorCode());
        }
    }

    /**
     * @return string
     */
    private function getSocketErrorMessage()
    {
        return socket_strerror($this->getSocketErrorCode());
    }

    /**
     * @return int
     */
    private function getSocketErrorCode()
    {
        return socket_last_error();
    }

    /**
     * @param int $length
     * @return string
     * @throws SocketException
     */
    public function readFromSocket($length)
    {
        $received = socket_recv($this->socket, $buf, $length, MSG_WAITALL);
        if ($length === $received) {
            return $buf;
        }

        // http://php.net/manual/en/function.socket-recv.php#47182
        if (0 === $received) {
            throw new SocketException(
                SocketException::SOCKET_DISCONNECTED_MESSAGE,
                SocketException::SOCKET_DISCONNECTED_CODE
            );
        }

        throw new SocketException($this->getSocketErrorMessage(), $this->getSocketErrorCode());
    }

    /**
     * @param string $data
     * @throws SocketException
     */
    public function writeToSocket($data)
    {
        if (!socket_write($this->socket, $data, strlen($data))) {
            throw new SocketException(
                SocketException::SOCKET_UNABLE_TO_WRITE_MESSAGE . $this->getSocketErrorMessage(),
                SocketException::SOCKET_UNABLE_TO_WRITE_CODE
            );
        }
    }
}