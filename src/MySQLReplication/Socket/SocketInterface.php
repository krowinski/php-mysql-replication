<?php
declare(strict_types=1);

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
    public function isConnected(): bool;

    /**
     * @param string $host
     * @param int $port
     * @throws SocketException
     */
    public function connectToStream(string $host, int $port): void;

    /**
     * @param int $length
     * @return string
     * @throws SocketException
     */
    public function readFromSocket(int $length): string;

    /**
     * @param string $data
     * @throws SocketException
     */
    public function writeToSocket(string $data): void;
}