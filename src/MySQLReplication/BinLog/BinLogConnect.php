<?php
namespace MySQLReplication\BinLog;

use MySQLReplication\Config\Config;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Definitions\ConstCapabilityFlags;
use MySQLReplication\Definitions\ConstCommand;
use MySQLReplication\Exception\BinLogException;
use MySQLReplication\Gtid\GtidCollection;

/**
 * Class Connect
 */
class BinLogConnect
{
    /**
     * @var resource
     */
    private $socket;
    /**
     * @var bool
     */
    private $checkSum = false;
    /**
     * @var MySQLRepository
     */
    private $DBHelper;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var BinLogAuth
     */
    private $packAuth;
    /**
     * @var GtidCollection
     */
    private $gtidCollection;

    /**
     * @param Config $config
     * @param MySQLRepository $DBHelper
     * @param BinLogAuth $packAuth
     * @param GtidCollection $gtidCollection
     */
    public function __construct(
        Config $config,
        MySQLRepository $DBHelper,
        BinLogAuth $packAuth,
        GtidCollection $gtidCollection
    ) {
        $this->DBHelper = $DBHelper;
        $this->config = $config;
        $this->packAuth = $packAuth;
        $this->gtidCollection = $gtidCollection;
    }

    public function __destruct()
    {
        if (true === $this->isConnected())
        {
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
     * @return bool
     */
    public function getCheckSum()
    {
        return $this->checkSum;
    }

    /**
     * @throws BinLogException
     * @return self
     */
    public function connectToStream()
    {
        if (false === ($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
        {
            throw new BinLogException('Unable to create a socket:' . socket_strerror(socket_last_error()), socket_last_error());
        }
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);

        if (false === socket_connect($this->socket, $this->config->getHost(), $this->config->getPort()))
        {
            throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
        }

        $this->serverInfo();
        $this->auth();
        $this->getBinlogStream();
    }

    private function serverInfo()
    {
        BinLogServerInfo::parsePackage($this->getPacket(false));
    }

    /**
     * @param bool $checkForOkByte
     * @return string
     * @throws BinLogException
     */
    public function getPacket($checkForOkByte = true)
    {
        $header = $this->readFromSocket(4);
        if (false === $header)
        {
            return false;
        }
        $dataLength = unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];

        $result = $this->readFromSocket($dataLength);
        if (true === $checkForOkByte)
        {
            $this->packAuth->isWriteSuccessful($result);
        }
        return $result;
    }

    /**
     * @param $length
     * @return string
     * @throws BinLogException
     */
    private function readFromSocket($length)
    {
        if ($length == 5)
        {
            throw new BinLogException('read 5 bytes from mysql server has gone away');
        }

        try
        {
            $bytes_read = 0;
            $body = '';
            while ($bytes_read < $length)
            {
                $resp = socket_read($this->socket, $length - $bytes_read);
                if ($resp === false)
                {
                    throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
                }

                // server kill connection or server gone away
                if (strlen($resp) === 0)
                {
                    throw new BinLogException('read less ' . ($length - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if (strlen($body) < $length)
            {
                throw new BinLogException('read less ' . ($length - strlen($body)));
            }
            return $body;
        } catch (\Exception $e)
        {
            throw new BinLogException(var_export($e, true));
        }
    }

    /**
     * @throws BinLogException
     */
    private function auth()
    {
        $data = $this->packAuth->createAuthenticationPacket(ConstCapabilityFlags::getCapabilities(), $this->config->getUser(), $this->config->getPassword(), BinLogServerInfo::getSalt());

        $this->writeToSocket($data);
        $this->getPacket();
    }

    /**
     * @param $data
     * @throws BinLogException
     */
    private function writeToSocket($data)
    {
        if (false === socket_write($this->socket, $data, strlen($data)))
        {
            throw new BinLogException('Unable to write to socket: ' . socket_strerror(socket_last_error()), socket_last_error());
        }
    }

    /**
     * @throws BinLogException
     */
    private function getBinlogStream()
    {
        $this->checkSum = $this->DBHelper->isCheckSum();
        if (true === $this->checkSum)
        {
            $this->execute('SET @master_binlog_checksum=@@global.binlog_checksum');
        }

        if (0 === $this->gtidCollection->count())
        {
            $binFilePos = $this->config->getBinLogPosition();
            $binFileName = $this->config->getBinLogFileName();

            if ('' === $binFilePos || '' === $binFileName)
            {
                $master = $this->DBHelper->getMasterStatus();
                $binFilePos = $master['Position'];
                $binFileName = $master['File'];
            }

            $prelude = pack('i', strlen($binFileName) + 11) . chr(ConstCommand::COM_BINLOG_DUMP);
            $prelude .= pack('I', $binFilePos);
            $prelude .= pack('v', 0);
            $prelude .= pack('I', $this->config->getSlaveId());
            $prelude .= $binFileName;
        }
        else
        {
            $prelude = pack('l', 26 + $this->gtidCollection->getEncodedPacketLength()) . chr(ConstCommand::COM_BINLOG_DUMP_GTID);
            $prelude .= pack('S', 0);
            $prelude .= pack('I', $this->config->getSlaveId());
            $prelude .= pack('I', 3);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= pack('Q', 4);

            $prelude .= pack('I', $this->gtidCollection->getEncodedPacketLength());
            $prelude .= $this->gtidCollection->getEncodedPacket();
        }

        $this->writeToSocket($prelude);
        $this->getPacket();
    }

    /**
     * @param string $sql
     * @throws BinLogException
     */
    private function execute($sql)
    {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC', $chunk_size, 0x03);

        $this->writeToSocket($prelude . $sql);
        $this->getPacket();
    }
}
