<?php
namespace MySQLReplication\BinLog;

use MySQLReplication\Config\Config;
use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Definitions\ConstCapabilityFlags;
use MySQLReplication\Definitions\ConstCommand;
use MySQLReplication\Exception\BinLogException;
use MySQLReplication\Pack\GtidSet;
use MySQLReplication\Pack\PackAuth;
use MySQLReplication\Pack\ServerInfo;

/**
 * Class Connect
 */
class Connect
{
    /**
     * @var resource
     */
    private $socket;
    /**
     * @var int
     */
    private $binFilePos;
    /**
     * @var string
     */
    private $binFileName;
    /**
     * @var string
     */
    private $gtID;
    /**
     * @var int
     */
    private $slaveId = 666;
    /**
     * @var bool
     */
    private $checkSum = false;
    /**
     * @var DBHelper
     */
    private $DBHelper;
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @param DBHelper $DBHelper
     * @param string $gtID
     * @param string $logFile
     * @param string $logPos
     * @param string $slave_id
     * @throws BinLogException
     */
    public function __construct(
        Config $config,
        DBHelper $DBHelper,
        $gtID = '',
        $logFile = '',
        $logPos = '',
        $slave_id = ''
    ) {
        $this->DBHelper = $DBHelper;
        $this->config = $config;

        $this->slaveId = empty($slave_id) ? $this->slaveId : $slave_id;
        $this->gtID = $gtID;
        $this->binFilePos = $logPos;
        $this->binFileName = $logFile;

        $this->connectToSocket();
    }

    /**
     * @return bool
     */
    public function getCheckSum()
    {
        return $this->checkSum;
    }

    public function __destruct()
    {
        if (true === $this->isConnected())
        {
            socket_shutdown($this->socket);
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    /**
     * @throws BinLogException
     * @return self
     */
    private function connectToSocket()
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
        ServerInfo::run($this->getPacket(false));
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
            PackAuth::success($result);
        }
        return $result;
    }

    /**
     * @return bool
     */
    private function isConnected()
    {
        return is_resource($this->socket);
    }

    /**
     * @param $data_len
     * @return string
     * @throws BinLogException
     */
    private function readFromSocket($data_len)
    {
        // server gone away
        if ($data_len == 5)
        {
            throw new BinLogException('read 5 bytes from mysql server has gone away');
        }

        try
        {
            $bytes_read = 0;
            $body = '';
            while ($bytes_read < $data_len)
            {
                $resp = socket_read($this->socket, $data_len - $bytes_read);
                if ($resp === false)
                {
                    throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
                }

                // server kill connection or server gone away
                if (strlen($resp) === 0)
                {
                    throw new BinLogException('read less ' . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if (strlen($body) < $data_len)
            {
                throw new BinLogException('read less ' . ($data_len - strlen($body)));
            }
            return $body;
        }
        catch (\Exception $e)
        {
            throw new BinLogException(var_export($e, true));
        }
    }

    /**
     * @throws BinLogException
     */
    private function auth()
    {
        $data = PackAuth::initPack(ConstCapabilityFlags::getCapabilities(), $this->config->getUser(), $this->config->getPassword(), ServerInfo::getSalt());

        $this->writeToSocket($data);
        $this->getPacket();
    }

    /**
     * @param $data
     * @return bool
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

        if ('' === $this->gtID)
        {
            if ('' === $this->binFilePos || '' === $this->binFileName)
            {
                $master = $this->DBHelper->getMasterStatus();
                $this->binFilePos = $master['Position'];
                $this->binFileName = $master['File'];
            }

            $prelude = pack('i', strlen($this->binFileName) + 11) . chr(ConstCommand::COM_BINLOG_DUMP);
            $prelude .= pack('I', $this->binFilePos);
            $prelude .= pack('v', 0);
            $prelude .= pack('I', $this->slaveId);
            $prelude .= $this->binFileName;
        }
        else
        {
            $gtID = new GtidSet($this->gtID);
            $encoded_data_size = $gtID->encoded_length();

            $prelude = pack('l', 26 + $encoded_data_size) . chr(ConstCommand::COM_BINLOG_DUMP_GTID);
            $prelude .= pack('S', 0);
            $prelude .= pack('I', $this->slaveId);
            $prelude .= pack('I', 3);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= pack('Q', 4);

            $prelude .= pack('I', $gtID->encoded_length());
            $prelude .= $gtID->encoded();
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
