<?php

namespace MySQLReplication\Socket;


/**
 * Class Socket
 * @package MySQLReplication\Socket
 */
interface SocketInterface
{
    /**
     * @return bool
     */
    public function isConnected();

    /**
     * @param string $host
     * @param int $port
     * @throws SocketException
     */
    public function connectToStream($host, $port);

    /**
     * @param int $length
     * @return string
     * @throws SocketException
     */
    public function readFromSocket($length);

    /**
     * @param string $data
     * @throws SocketException
     */
    public function writeToSocket($data);
}