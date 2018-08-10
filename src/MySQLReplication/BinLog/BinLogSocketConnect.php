<?php

namespace MySQLReplication\BinLog;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Config\Config;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidException;
use MySQLReplication\Gtid\GtidFactory;
use MySQLReplication\Repository\RepositoryInterface;
use MySQLReplication\Socket\SocketInterface;

/**
 * Class BinLogSocketConnect
 * @package MySQLReplication\BinLog
 */
class BinLogSocketConnect
{
    const COM_BINLOG_DUMP = 0x12;
    const COM_REGISTER_SLAVE = 0x15;
    const COM_BINLOG_DUMP_GTID = 0x1e;

    /**
     * @var bool
     */
    private $checkSum = false;
    /**
     * @var RepositoryInterface
     */
    private $repository;
    /**
     * http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html 00 FE
     * @var array
     */
    private $packageOkHeader = [0, 254];
    /**
     * @var SocketInterface
     */
    private $socket;
    /**
     * 2^24 - 1 16m
     * @var int
     */
    private $binaryDataMaxLength = 16777215;
    /**
     * @var BinLogCurrent
     */
    private $binLogCurrent;

    /**
     * @param RepositoryInterface $repository
     * @param SocketInterface $socket
     * @throws BinLogException
     * @throws \MySQLReplication\Gtid\GtidException
     * @throws \MySQLReplication\Socket\SocketException
     */
    public function __construct(
        RepositoryInterface $repository,
        SocketInterface $socket
    ) {
        $this->repository = $repository;
        $this->socket = $socket;
        $this->binLogCurrent = new BinLogCurrent();

        $this->socket->connectToStream(Config::getHost(), Config::getPort());
        BinLogServerInfo::parsePackage($this->getResponse(false), $this->repository->getVersion());
        $this->authenticate();
        $this->getBinlogStream();
    }

    /**
     * @param bool $checkResponse
     * @return string
     * @throws \MySQLReplication\BinLog\BinLogException
     * @throws \MySQLReplication\Socket\SocketException
     */
    public function getResponse($checkResponse = true)
    {
        $header = $this->socket->readFromSocket(4);
        if ('' === $header) {
            return '';
        }
        $dataLength = unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];
        $isMaxDataLength = $dataLength === $this->binaryDataMaxLength;

        $result = $this->socket->readFromSocket($dataLength);
        if (true === $checkResponse) {
            $this->isWriteSuccessful($result, $isMaxDataLength);
        }

        //https://dev.mysql.com/doc/internals/en/sending-more-than-16mbyte.html
        while ($isMaxDataLength) {
            $header = $this->socket->readFromSocket(4);
            if ('' === $header) {
                return $result;
            }
            $dataLength = unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];
            $isMaxDataLength = $dataLength === $this->binaryDataMaxLength;

            $next_result = $this->socket->readFromSocket($dataLength);
            if (true === $checkResponse) {
                $this->isWriteSuccessful($next_result, $isMaxDataLength);
            }
            $result .= $next_result;
        }

        return $result;
    }

    /**
     * @param string $data
     * @param bool   $isMaxDataLength
     *
     * @throws BinLogException
     */
    private function isWriteSuccessful($data, $isMaxDataLength = false)
    {
        if ($isMaxDataLength) {
            return;
        }
        $head = ord($data[0]);
        if (!in_array($head, $this->packageOkHeader, true)) {
            $errorCode = unpack('v', $data[1] . $data[2])[1];
            $errorMessage = '';
            $packetLength = strlen($data);
            for ($i = 9; $i < $packetLength; ++$i) {
                $errorMessage .= $data[$i];
            }

            throw new BinLogException($errorMessage, $errorCode);
        }
    }

    /**
     * @throws BinLogException
     * @throws \MySQLReplication\Socket\SocketException
     * @link http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
     */
    private function authenticate()
    {
        $data = pack('L', self::getCapabilities());
        $data .= pack('L', $this->binaryDataMaxLength);
        $data .= chr(33);
        for ($i = 0; $i < 23; $i++) {
            $data .= chr(0);
        }
        $result = sha1(Config::getPassword(), true) ^ sha1(
                BinLogServerInfo::getSalt() . sha1(sha1(Config::getPassword(), true), true), true
            );

        $data = $data . Config::getUser() . chr(0) . chr(strlen($result)) . $result;
        $str = pack('L', strlen($data));
        $s = $str[0] . $str[1] . $str[2];
        $data = $s . chr(1) . $data;

        $this->socket->writeToSocket($data);
        $this->getResponse();
    }

    /**
     * http://dev.mysql.com/doc/internals/en/capability-flags.html#packet-protocol::capabilityflags
     * https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
     * @return int
     */
    private static function getCapabilities()
    {
        /*
            Left only as information
            $foundRows = 1 << 1;
            $connectWithDb = 1 << 3;
            $compress = 1 << 5;
            $odbc = 1 << 6;
            $localFiles = 1 << 7;
            $ignoreSpace = 1 << 8;
            $multiStatements = 1 << 16;
            $multiResults = 1 << 17;
            $interactive = 1 << 10;
            $ssl = 1 << 11;
            $ignoreSigPipe = 1 << 12;
        */

        $noSchema = 1 << 4;
        $longPassword = 1;
        $longFlag = 1 << 2;
        $transactions = 1 << 13;
        $secureConnection = 1 << 15;
        $protocol41 = 1 << 9;

        return ($longPassword | $longFlag | $transactions | $protocol41 | $secureConnection | $noSchema);
    }

    /**
     * @throws BinLogException
     * @throws GtidException
     * @throws \MySQLReplication\Socket\SocketException
     */
    private function getBinlogStream()
    {
        $this->checkSum = $this->repository->isCheckSum();
        if ($this->checkSum) {
            $this->execute('SET @master_binlog_checksum = @@global.binlog_checksum');
        }

        if (0 !== Config::getHeartbeatPeriod()) {
            // master_heartbeat_period is in nanoseconds
            $this->execute('SET @master_heartbeat_period = ' . Config::getHeartbeatPeriod() * 1000000000);
        }

        $this->registerSlave();

        if ('' !== Config::getMariaDbGtid()) {
            $this->setBinLogDumpMariaGtid();
        }
        if ('' !== Config::getGtid()) {
            $this->setBinLogDumpGtid();
        } else {
            $this->setBinLogDump();
        }
    }

    /**
     * @param string $sql
     * @throws BinLogException
     * @throws \MySQLReplication\Socket\SocketException
     */
    private function execute($sql)
    {
        $this->socket->writeToSocket(pack('LC', strlen($sql) + 1, 0x03) . $sql);
        $this->getResponse();
    }

    /**
     * @see https://dev.mysql.com/doc/internals/en/com-register-slave.html
     * @throws BinLogException
     * @throws \MySQLReplication\Socket\SocketException
     */
    private function registerSlave()
    {
        $host = gethostname();
        $hostLength = strlen($host);
        $userLength = strlen(Config::getUser());
        $passLength = strlen(Config::getPassword());

        $data = pack('l', 18 + $hostLength + $userLength + $passLength);
        $data .= chr(self::COM_REGISTER_SLAVE);
        $data .= pack('V', Config::getSlaveId());
        $data .= pack('C', $hostLength);
        $data .= $host;
        $data .= pack('C', $userLength);
        $data .= Config::getUser();
        $data .= pack('C', $passLength);
        $data .= Config::getPassword();
        $data .= pack('v', Config::getPort());
        $data .= pack('V', 0);
        $data .= pack('V', 0);

        $this->socket->writeToSocket($data);
        $this->getResponse();
    }

    /**
     * @throws \MySQLReplication\Socket\SocketException
     * @throws \MySQLReplication\BinLog\BinLogException
     */
    private function setBinLogDumpMariaGtid()
    {
        $this->execute('SET @mariadb_slave_capability = 4');
        $this->execute('SET @slave_connect_state = \'' . Config::getMariaDbGtid() . '\'');
        $this->execute('SET @slave_gtid_strict_mode = 0');
        $this->execute('SET @slave_gtid_ignore_duplicates = 0');

        $this->binLogCurrent->setMariaDbGtid(Config::getMariaDbGtid());
    }

    /**
     * @see https://dev.mysql.com/doc/internals/en/com-binlog-dump-gtid.html
     * @throws BinLogException
     * @throws GtidException
     * @throws \MySQLReplication\Socket\SocketException
     */
    private function setBinLogDumpGtid()
    {
        $collection = GtidFactory::makeCollectionFromString(Config::getGtid());

        $data = pack('l', 26 + $collection->getEncodedLength()) . chr(self::COM_BINLOG_DUMP_GTID);
        $data .= pack('S', 0);
        $data .= pack('I', Config::getSlaveId());
        $data .= pack('I', 3);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);
        $data .= BinaryDataReader::pack64bit(4);
        $data .= pack('I', $collection->getEncodedLength());
        $data .= $collection->getEncoded();

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->binLogCurrent->setGtid(Config::getGtid());
    }

    /**
     * @see https://dev.mysql.com/doc/internals/en/com-binlog-dump.html
     * @throws BinLogException
     * @throws \MySQLReplication\Socket\SocketException
     */
    private function setBinLogDump()
    {
        $binFilePos = Config::getBinLogPosition();
        $binFileName = Config::getBinLogFileName();
        if (0 === $binFilePos && '' === $binFileName) {
            $master = $this->repository->getMasterStatus();
            if ([] === $master) {
                throw new BinLogException(
                    MySQLReplicationException::BINLOG_NOT_ENABLED,
                    MySQLReplicationException::BINLOG_NOT_ENABLED_CODE
                );
            }
            $binFilePos = $master['Position'];
            $binFileName = $master['File'];
        }

        $data = pack('i', strlen($binFileName) + 11) . chr(self::COM_BINLOG_DUMP);
        $data .= pack('I', $binFilePos);
        $data .= pack('v', 0);
        $data .= pack('I', Config::getSlaveId());
        $data .= $binFileName;

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->binLogCurrent->setBinLogPosition($binFilePos);
        $this->binLogCurrent->setBinFileName($binFileName);
    }

    /**
     * @return BinLogCurrent
     */
    public function getBinLogCurrent()
    {
        return $this->binLogCurrent;
    }

    /**
     * @return bool
     */
    public function getCheckSum()
    {
        return $this->checkSum;
    }
}
