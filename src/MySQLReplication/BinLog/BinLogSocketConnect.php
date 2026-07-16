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
    private const AUTH_SWITCH_PACKET = 254;
    /**
     * @see https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_replication_semisync.html
     */
    private const SEMI_SYNC_INDICATOR = 0xef;
    /**
     * https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_connection_phase.html 00 FE
     */
    private array $packageOkHeader = [0, 1, 254];
    private int $binaryDataMaxLength = 16777215;
    private bool $checkSum = false;
    private bool $semiSyncEnabled = false;
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

        $this->logger->debug('Connected to ' . $config->host . ':' . $config->port);

        $this->binLogServerInfo = BinLogServerInfo::make($this->getResponse(false), $this->repository->getVersion());

        $this->logger->debug('Server version name: ' . $this->binLogServerInfo->versionName . ', revision: ' . $this->binLogServerInfo->versionRevision);


        $this->authenticate($this->binLogServerInfo->authPlugin);
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

    public function getSemiSyncEnabled(): bool
    {
        return $this->semiSyncEnabled;
    }

    /**
     * @see https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_replication_semisync.html
     */
    public function sendSemiSyncAck(): void
    {
        $payload = chr(self::SEMI_SYNC_INDICATOR)
            . BinaryDataReader::pack64bit((int)$this->binLogCurrent->getBinLogPosition())
            . $this->binLogCurrent->getBinFileName();

        $this->socket->writeToSocket(pack('l', strlen($payload)) . $payload);

        $this->logger->debug('Semi-sync ACK sent for ' . $this->binLogCurrent->getBinFileName() . ':' . $this->binLogCurrent->getBinLogPosition());
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

    private function authenticate(BinLogAuthPluginMode $authPlugin): void
    {
        $this->logger->debug('Trying to authenticate user: ' . $this->config->user . ' using ' . $authPlugin->value . ' default plugin');

        $data = pack('L', self::getCapabilities());
        $data .= pack('L', $this->binaryDataMaxLength);
        $data .= chr(33);
        $data .= str_repeat(chr(0), 23);
        $data .= $this->config->user . chr(0);
        $auth = $this->getAuthData($authPlugin, $this->binLogServerInfo->salt);
        $data .= chr(strlen($auth) & 0xFF) . $auth;
        $data .= $authPlugin->value . chr(0);
        $str = pack('L', strlen($data));
        $s = $str[0] . $str[1] . $str[2];
        $data = $s . chr(1) . $data;

        $this->socket->writeToSocket($data);
        $response = $this->getResponse();

        // Check for AUTH_SWITCH_PACKET
        if (isset($response[0]) && ord($response[0]) === self::AUTH_SWITCH_PACKET) {
            $this->switchAuth($response);
        }

        $this->logger->debug('User authenticated');
    }

    private function getAuthData(?BinLogAuthPluginMode $authPlugin, string $salt): string
    {
        if ($authPlugin === BinLogAuthPluginMode::MysqlNativePassword) {
            return $this->authenticateMysqlNativePasswordPlugin($salt);
        }

        if ($authPlugin === BinLogAuthPluginMode::CachingSha2Password) {
            return $this->authenticateCachingSha2PasswordPlugin($salt);
        }

        return '';
    }

    private function authenticateCachingSha2PasswordPlugin(string $salt): string
    {
        $hash1 = hash('sha256', $this->config->password, true);
        $hash2 = hash('sha256', $hash1, true);
        $hash3 = hash('sha256', $hash2 . $salt, true);
        return $hash1 ^ $hash3;
    }

    private function authenticateMysqlNativePasswordPlugin(string $salt): string
    {
        $hash1 = sha1($this->config->password, true);
        $hash2 = sha1($salt . sha1(sha1($this->config->password, true), true), true);
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
        if (!$this->repository->isRowFormat()) {
            throw new BinLogException(MySQLReplicationException::BINLOG_FORMAT_NOT_ROW, MySQLReplicationException::BINLOG_FORMAT_NOT_ROW_CODE);
        }

        if (!$this->repository->isRowImageFull()) {
            $this->logger->warning('binlog_row_image is not FULL - row events may contain partial column data.');
        }

        $this->checkSum = $this->repository->isCheckSum();
        if ($this->checkSum) {
            $this->executeSQL('SET @master_binlog_checksum = @@global.binlog_checksum');
        }

        if ($this->config->heartbeatPeriod > 0.00) {
            // master_heartbeat_period is in nanoseconds
            // MariaDB never adopted MySQL 8.0.26+'s master->source renaming, so a
            // MariaDB version string (e.g. "10.6.15-MariaDB") must not take this branch
            // even though version_compare() would otherwise treat "10" as >= "8.4.0".
            if (!$this->binLogServerInfo->isMariaDb() && version_compare($this->repository->getVersion(), '8.4.0') >= 0) {
                $this->executeSQL('SET @source_heartbeat_period = ' . $this->config->heartbeatPeriod * 1000000000);
            } else {
                $this->executeSQL('SET @master_heartbeat_period = ' . $this->config->heartbeatPeriod * 1000000000);
            }

            $this->logger->debug('Heartbeat period set to ' . $this->config->heartbeatPeriod . ' seconds');
        }

        if ($this->config->slaveUuid !== '') {
            $this->executeSQL('SET @slave_uuid = \'' . $this->config->slaveUuid . '\', @replica_uuid = \'' . $this->config->slaveUuid . '\'');

            $this->logger->debug('Slave uuid set to ' . $this->config->slaveUuid);
        }

        if ($this->config->semiSync) {
            if ($this->repository->isSemiSyncEnabled()) {
                $this->executeSQL('SET @rpl_semi_sync_slave = 1');
                $this->semiSyncEnabled = true;

                $this->logger->debug('Semi-sync replication enabled');
            } else {
                $this->logger->warning('Semi-sync replication requested but the master does not have it enabled ' . '(rpl_semi_sync_master_enabled/rpl_semi_sync_source_enabled is not ON) - ' . 'falling back to asynchronous replication.');
            }
        }

        $this->registerSlave();

        $mariaDbGtid = $this->config->mariaDbGtid;
        $gtid = $this->config->gtid;

        // no explicit position given - ask the master for its current GTID set
        // and start streaming from there instead of falling back to file+position
        if ($this->config->gtidAutoPosition && $mariaDbGtid === '' && $gtid === '') {
            if ($this->binLogServerInfo->isMariaDb()) {
                $mariaDbGtid = $this->repository->getGtidExecuted();
            } else {
                $gtid = $this->repository->getGtidExecuted();
            }
        }

        if ($mariaDbGtid !== '') {
            $this->setBinLogDumpMariaGtid($mariaDbGtid);
        }
        if ($gtid !== '') {
            $this->setBinLogDumpGtid($gtid);
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

        $this->logger->debug('Slave registered with id ' . $this->config->slaveId);
    }

    private function setBinLogDumpMariaGtid(string $mariaDbGtid): void
    {
        $this->executeSQL('SET @mariadb_slave_capability = 4');
        $this->executeSQL('SET @slave_connect_state = \'' . $mariaDbGtid . '\'');
        $this->executeSQL('SET @slave_gtid_strict_mode = 0');
        $this->executeSQL('SET @slave_gtid_ignore_duplicates = 0');

        $this->binLogCurrent->setMariaDbGtid($mariaDbGtid);

        $this->logger->debug('Set Maria GTID to start from: ' . $mariaDbGtid);
    }

    private function setBinLogDumpGtid(string $gtid): void
    {
        $collection = GtidCollection::makeCollectionFromString($gtid);

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

        $this->binLogCurrent->setGtid($gtid);

        $this->logger->debug('Set GTID to start from: ' . $gtid);
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

        $this->logger->debug('Set binlog to start from: ' . $binFileName . ':' . $binFilePos);
    }

    private function switchAuth(string $response): void
    {
        // skip AUTH_SWITCH_PACKET byte
        $offset = 1;
        $authPluginSwitched = BinLogAuthPluginMode::make(BinaryDataReader::decodeNullLength($response, $offset));
        $salt = BinaryDataReader::decodeNullLength($response, $offset);
        $auth = $this->getAuthData($authPluginSwitched, $salt);

        $this->logger->debug('Auth switch packet received, switching to ' . $authPluginSwitched->value);

        $this->socket->writeToSocket(pack('L', (strlen($auth)) | (3 << 24)) . $auth);

        // caching_sha2_password sends an AuthMoreData packet (0x01 status byte)
        // followed by either fast_auth_success (0x03) or perform_full_authentication
        // (0x04). Both packets must be drained here so that subsequent setup
        // commands (executeSQL, registerSlave, setBinLogDump) each see their own
        // response on the wire rather than a leftover auth packet.
        //
        // Without this drain, every getResponse() call in getBinlogStream() is
        // offset by 2 packets, and the final misaligned packet reaches
        // Event::consume() where its 0x00 status byte is misread as a binlog
        // event header, causing a TypeError on readInt32() (PHP 8 strict types)
        // or an unpack() underflow on older PHP.
        //
        // See: https://dev.mysql.com/doc/dev/mysql-server/latest/page_caching_sha2_authentication_exchanges.html
        if ($authPluginSwitched === BinLogAuthPluginMode::CachingSha2Password) {
            $authMoreData = $this->getResponse(false);
            if ($authMoreData !== '' && ord($authMoreData[0]) === 0x01) {
                $marker = isset($authMoreData[1]) ? ord($authMoreData[1]) : 0x00;
                if ($marker === 0x04) {
                    // Server is requesting full authentication (password not yet in
                    // server cache). Full-auth requires sending the password over a
                    // secure channel (TLS or unix socket) which this client does not
                    // currently implement. Surface a clear error rather than hanging.
                    throw new BinLogException(
                        'caching_sha2_password full authentication required ' .
                        '(server sent perform_full_authentication 0x04). ' .
                        'This client supports only fast-auth (the user must already ' .
                        'be in the server auth cache, or connect via TLS/unix socket).',
                        0
                    );
                }
                // 0x03 = fast_auth_success: server now sends the final auth OK packet.
                $this->getResponse();
            }
            // If the first byte was not 0x01 the server skipped AuthMoreData and
            // sent the OK directly — nothing more to drain.
        }
    }
}
