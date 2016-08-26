<?php
namespace MySQLReplication\BinLog;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\Exception\BinLogException;
use MySQLReplication\Config\Config;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Definitions\ConstCapabilityFlags;
use MySQLReplication\Definitions\ConstCommand;
use MySQLReplication\Gtid\GtidCollection;

/**
 * Class BinLogConnect
 * @package MySQLReplication\BinLog
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
    private $mySQLRepository;
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
     * http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html 00 FE
     * @var array
     */
    private $packageOkHeader = [0, 254];

    /**
     * @param Config $config
     * @param MySQLRepository $mySQLRepository
     * @param BinLogAuth $packAuth
     * @param GtidCollection $gtidCollection
     */
    public function __construct(
        Config $config,
        MySQLRepository $mySQLRepository,
        BinLogAuth $packAuth,
        GtidCollection $gtidCollection
    ) {
        $this->mySQLRepository = $mySQLRepository;
        $this->config = $config;
        $this->packAuth = $packAuth;
        $this->gtidCollection = $gtidCollection;
    }

    /**
     *
     */
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
     */
    public function connectToStream()
    {
        if (false === ($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
        {
            throw new BinLogException('Unable to create a socket:' . socket_strerror(socket_last_error()), socket_last_error());
        }
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);

        if (false === socket_connect($this->socket, $this->config->getIp(), $this->config->getPort()))
        {
            throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
        }

        $this->serverInfo();
        $this->auth();
        $this->getBinlogStream();
    }

    /**
     *
     */
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
            $this->isWriteSuccessful($result);
        }
        return $result;
    }

    /**
     * @param $packet
     * @return array
     * @throws BinLogException
     */
    public function isWriteSuccessful($packet)
    {
        $head = ord($packet[0]);
        if (in_array($head, $this->packageOkHeader, true))
        {
            return ['status' => true, 'code' => 0, 'msg' => ''];
        }
        else
        {
            $error_code = unpack('v', $packet[1] . $packet[2])[1];
            $error_msg = '';
            $packetLength = strlen($packet);
            for ($i = 9; $i < $packetLength; $i++)
            {
                $error_msg .= $packet[$i];
            }

            throw new BinLogException($error_msg, $error_code);
        }
    }

    /**
     * @param $length
     * @return mixed
     * @throws BinLogException
     */
    private function readFromSocket($length)
    {
        if ($length == 5)
        {
            throw new BinLogException('read 5 bytes from mysql server has gone away');
        }

        if ($length === socket_recv($this->socket, $buf, $length, MSG_WAITALL))
        {
            return $buf;
        }

        throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
    }

    /**
     * @throws BinLogException
     */
    private function auth()
    {
        $data = $this->packAuth->createAuthenticationBinary(
            ConstCapabilityFlags::getCapabilities(),
            $this->config->getUser(),
            $this->config->getPassword(),
            BinLogServerInfo::getSalt()
        );

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
        $this->checkSum = $this->mySQLRepository->isCheckSum();
        if (true === $this->checkSum)
        {
            $this->execute('SET @master_binlog_checksum=@@global.binlog_checksum');
        }

        if (0 === $this->gtidCollection->count())
        {
            $binFilePos = $this->config->getBinLogPosition();
            $binFileName = $this->config->getBinLogFileName();

            if ('' !== $this->config->getMariaDbGtid())
            {
                $this->execute("SET @mariadb_slave_capability = 4");
                $this->execute("SET @slave_connect_state = '" . $this->config->getMariaDbGtid() . "'");
                $this->execute("SET @slave_gtid_strict_mode = 0");
                $this->execute("SET @slave_gtid_ignore_duplicates = 0");
            }
            
            if ('' === $binFilePos || '' === $binFileName)
            {
                $master = $this->mySQLRepository->getMasterStatus();
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
            $prelude = pack('l', 26 + $this->gtidCollection->getEncodedLength()) . chr(ConstCommand::COM_BINLOG_DUMP_GTID);
            $prelude .= pack('S', 0);
            $prelude .= pack('I', $this->config->getSlaveId());
            $prelude .= pack('I', 3);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= BinaryDataReader::pack64bit(4);

            $prelude .= pack('I', $this->gtidCollection->getEncodedLength());
            $prelude .= $this->gtidCollection->getEncoded();
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
