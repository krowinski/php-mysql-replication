<?php

declare(strict_types=1);

namespace MySQLReplication\BinLog;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Config\Config;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidCollection;
use MySQLReplication\Repository\RepositoryInterface;
use MySQLReplication\Socket\SocketInterface;
use Psr\Log\LoggerInterface;

class BinLogSocketConnect
{
    private const COM_BINLOG_DUMP = 0x12;
    private const COM_REGISTER_SLAVE = 0x15;
    private const COM_BINLOG_DUMP_GTID = 0x1e;
    /**
     * https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_connection_phase.html 00 FE
     */
    private array $packageOkHeader = [0, 1, 254];
    private int $binaryDataMaxLength = 16777215;
    private bool $checkSum = false;
    private BinLogCurrent $binLogCurrent;
    private BinLogServerInfo $binLogServerInfo;

    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly SocketInterface $socket,
        private readonly LoggerInterface $logger,
        private readonly Config $config
    ) {
        $this->binLogCurrent = new BinLogCurrent();

        $this->socket->connectToStream($config->host, $config->port);

        $this->logger->info('Connected to ' . $config->host . ':' . $config->port);

        $this->binLogServerInfo = BinLogServerInfo::make(
            $this->getResponse(false),
            $this->repository->getVersion()
        );

        $this->logger->info(
            'Server version name: ' . $this->binLogServerInfo->versionName . ', revision: ' . $this->binLogServerInfo->versionRevision
        );

        $this->authenticate();
        $this->getBinlogStream();
    }

    public function getBinLogServerInfo(): BinLogServerInfo
    {
        return $this->binLogServerInfo;
    }

    public function getResponse(bool $checkResponse = true): string
    {
        $header = $this->socket->readFromSocket(4);
        if ($header === '') {
            return '';
        }
        $dataLength = BinaryDataReader::unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];
        $isMaxDataLength = $dataLength === $this->binaryDataMaxLength;

        $result = $this->socket->readFromSocket($dataLength);
        if ($checkResponse === true) {
            $this->isWriteSuccessful($result);
        }

        // https://dev.mysql.com/doc/internals/en/sending-more-than-16mbyte.html
        while ($isMaxDataLength) {
            $header = $this->socket->readFromSocket(4);
            if ($header === '') {
                return $result;
            }
            $dataLength = BinaryDataReader::unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];
            $isMaxDataLength = $dataLength === $this->binaryDataMaxLength;
            $next_result = $this->socket->readFromSocket($dataLength);
            $result .= $next_result;
        }

        return $result;
    }

    public function getBinLogCurrent(): BinLogCurrent
    {
        return $this->binLogCurrent;
    }

    public function getCheckSum(): bool
    {
        return $this->checkSum;
    }

    private function isWriteSuccessful(string $data): void
    {
        $head = ord($data[0]);
        if (!in_array($head, $this->packageOkHeader, true)) {
            $errorCode = BinaryDataReader::unpack('v', $data[1] . $data[2])[1];
            $errorMessage = '';
            $packetLength = strlen($data);
            for ($i = 9; $i < $packetLength; ++$i) {
                $errorMessage .= $data[$i];
            }

            throw new BinLogException($errorMessage, $errorCode);
        }
    }

    private function authenticate(): void
    {
        if ($this->binLogServerInfo->authPlugin === null) {
            throw new MySQLReplicationException(
                MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED,
                MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED_CODE
            );
        }

        $this->logger->info(
            'Trying to authenticate user: ' . $this->config->user . ' using ' . $this->binLogServerInfo->authPlugin->value . ' plugin'
        );

        $data = pack('L', self::getCapabilities());
        $data .= pack('L', $this->binaryDataMaxLength);
        $data .= chr(33);
        $data .= str_repeat(chr(0), 23);
        $data .= $this->config->user . chr(0);

        $auth = '';
        if ($this->binLogServerInfo->authPlugin === BinLogAuthPluginMode::MysqlNativePassword) {
            $auth = $this->authenticateMysqlNativePasswordPlugin();
        } elseif ($this->binLogServerInfo->authPlugin === BinLogAuthPluginMode::CachingSha2Password) {
            $auth = $this->authenticateCachingSha2PasswordPlugin();
        }

        $data .= chr(strlen($auth)) . $auth;
        $data .= $this->binLogServerInfo->authPlugin->value . chr(0);
        $str = pack('L', strlen($data));
        $s = $str[0] . $str[1] . $str[2];
        $data = $s . chr(1) . $data;

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->logger->info('User authenticated');
    }

    private function authenticateCachingSha2PasswordPlugin(): string
    {
        $hash1 = hash('sha256', $this->config->password, true);
        $hash2 = hash('sha256', $hash1, true);
        $hash3 = hash('sha256', $hash2 . $this->binLogServerInfo->salt, true);
        return $hash1 ^ $hash3;
    }

    private function authenticateMysqlNativePasswordPlugin(): string
    {
        $hash1 = sha1($this->config->password, true);
        $hash2 = sha1($this->binLogServerInfo->salt . sha1(sha1($this->config->password, true), true), true);
        return $hash1 ^ $hash2;
    }

    /**
     * https://dev.mysql.com/doc/dev/mysql-server/latest/group__group__cs__capabilities__flags.html
     * https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
     */
    private static function getCapabilities(): int
    {
        $noSchema = 1 << 4;
        $longPassword = 1;
        $longFlag = 1 << 2;
        $transactions = 1 << 13;
        $secureConnection = 1 << 15;
        $protocol41 = 1 << 9;
        $authPlugin = 1 << 19;

        return $longPassword | $longFlag | $transactions | $protocol41 | $secureConnection | $noSchema | $authPlugin;
    }

    private function getBinlogStream(): void
    {
        $this->checkSum = $this->repository->isCheckSum();
        if ($this->checkSum) {
            $this->executeSQL('SET @master_binlog_checksum = @@global.binlog_checksum');
        }

        if ($this->config->heartbeatPeriod > 0.00) {
            // master_heartbeat_period is in nanoseconds
            $this->executeSQL('SET @master_heartbeat_period = ' . $this->config->heartbeatPeriod * 1000000000);

            $this->logger->info('Heartbeat period set to ' . $this->config->heartbeatPeriod . ' seconds');
        }

        if ($this->config->slaveUuid !== '') {
            $this->executeSQL(
                'SET @slave_uuid = \'' . $this->config->slaveUuid . '\', @replica_uuid = \'' . $this->config->slaveUuid . '\''
            );

            $this->logger->info('Salve uuid set to ' . $this->config->slaveUuid);
        }

        $this->registerSlave();

        if ($this->config->mariaDbGtid !== '') {
            $this->setBinLogDumpMariaGtid();
        }
        if ($this->config->gtid !== '') {
            $this->setBinLogDumpGtid();
        } else {
            $this->setBinLogDump();
        }
    }

    private function executeSQL(string $sql): void
    {
        $this->socket->writeToSocket(pack('LC', strlen($sql) + 1, 0x03) . $sql);
        $this->getResponse();
    }

    /**
     * @see https://dev.mysql.com/doc/internals/en/com-register-slave.html
     */
    private function registerSlave(): void
    {
        $host = (string)gethostname();
        $hostLength = strlen($host);
        $userLength = strlen($this->config->user);
        $passLength = strlen($this->config->password);

        $data = pack('l', 18 + $hostLength + $userLength + $passLength);
        $data .= chr(self::COM_REGISTER_SLAVE);
        $data .= pack('V', $this->config->slaveId);
        $data .= pack('C', $hostLength);
        $data .= $host;
        $data .= pack('C', $userLength);
        $data .= $this->config->user;
        $data .= pack('C', $passLength);
        $data .= $this->config->password;
        $data .= pack('v', $this->config->port);
        $data .= pack('V', 0);
        $data .= pack('V', 0);

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->logger->info('Slave registered with id ' . $this->config->slaveId);
    }

    private function setBinLogDumpMariaGtid(): void
    {
        $this->executeSQL('SET @mariadb_slave_capability = 4');
        $this->executeSQL('SET @slave_connect_state = \'' . $this->config->mariaDbGtid . '\'');
        $this->executeSQL('SET @slave_gtid_strict_mode = 0');
        $this->executeSQL('SET @slave_gtid_ignore_duplicates = 0');

        $this->binLogCurrent->setMariaDbGtid($this->config->mariaDbGtid);

        $this->logger->info('Set Maria GTID to start from: ' . $this->config->mariaDbGtid);
    }

    private function setBinLogDumpGtid(): void
    {
        $collection = GtidCollection::makeCollectionFromString($this->config->gtid);

        $data = pack('l', 26 + $collection->getEncodedLength()) . chr(self::COM_BINLOG_DUMP_GTID);
        $data .= pack('S', 0);
        $data .= pack('I', $this->config->slaveId);
        $data .= pack('I', 3);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);
        $data .= BinaryDataReader::pack64bit(4);
        $data .= pack('I', $collection->getEncodedLength());
        $data .= $collection->getEncoded();

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->binLogCurrent->setGtid($this->config->gtid);

        $this->logger->info('Set GTID to start from: ' . $this->config->gtid);
    }

    /**
     * 1              [12] COM_BINLOG_DUMP
     * 4              binlog-pos
     * 2              flags
     * 4              server-id
     * string[EOF]    binlog-filename
     */
    private function setBinLogDump(): void
    {
        $binFilePos = $this->config->binLogPosition;
        $binFileName = $this->config->binLogFileName;
        // if not set start from newest binlog
        if ($binFilePos === '' && $binFileName === '') {
            $masterStatusDTO = $this->repository->getMasterStatus();
            $binFilePos = $masterStatusDTO->position;
            $binFileName = $masterStatusDTO->file;
        }

        $data = pack('i', strlen($binFileName) + 11) . chr(self::COM_BINLOG_DUMP);
        $data .= pack('I', $binFilePos);
        $data .= pack('v', 0);
        $data .= pack('I', $this->config->slaveId);
        $data .= $binFileName;

        $this->socket->writeToSocket($data);
        $this->getResponse();

        $this->binLogCurrent->setBinLogPosition($binFilePos);
        $this->binLogCurrent->setBinFileName($binFileName);

        $this->logger->info('Set binlog to start from: ' . $binFileName . ':' . $binFilePos);
    }
}
