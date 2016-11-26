<?php
namespace MySQLReplication\BinLog;

use MySQLReplication\BinLog\Exception\BinLogException;


/**
 * Class SocketConnect
 * @package MySQLReplication\BinLog
 */
interface BinLogSocketConnectInterface
{
    /**
     * @return bool
     */
    public function isConnected();

    /**
     * @return bool
     */
    public function getCheckSum();

    /**
     * @throws BinLogException
     */
    public function connectToStream();

    /**
     * @param bool $checkForOkByte
     * @return string
     * @throws BinLogException
     */
    public function getPacket($checkForOkByte = true);

    /**
     * @param string $packet
     * @return array
     * @throws BinLogException
     */
    public function isWriteSuccessful($packet);
}