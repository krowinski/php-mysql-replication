<?php
namespace MySQLReplication\BinLog;

use MySQLReplication\Config\Config;
use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Definitions\ConstCapability;
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
     * @var int
     */
    public static $FILE_POS;
    /**
     * @var int
     */
    private static $_FLAG = 0;
    /**
     * @var resource
     */
    private static $_SOCKET;
    /**
     * @var string
     */
    private static $_USER;
    /**
     * @var string
     */
    private static $_PASS;
    /**
     * @var int
     */
    private static $_PORT;
    /**
     * @var string
     */
    private static $_HOST;
    /**
     * @var string
     */
    private static $_SALT;
    /**
     * @var int
     */
    private static $_POS;
    /**
     * @var string
     */
    private static $_FILE;
    /**
     * @var string
     */
    private static $_GTID;
    /**
     * @var int
     */
    private static $_SLAVE_SERVER_ID = 666;
    /**
     * @var bool
     */
    private static $_CHECKSUM = false;

    /**
     * @param string $gtid
     * @param string $logFile
     * @param string $logPos
     * @param string $slave_id
     * @throws BinLogException
     */
    public static function init(
        $gtid = '',
        $logFile = '',
        $logPos = '',
        $slave_id = ''
    ) {
        self::$_SLAVE_SERVER_ID = empty($slave_id) ? self::$_SLAVE_SERVER_ID : $slave_id;
        self::$_GTID = $gtid;
        self::$_HOST = Config::$DB_CONFIG['host'];
        self::$_USER = Config::$DB_CONFIG['user'];
        self::$_PASS = Config::$DB_CONFIG['password'];
        self::$_PORT = Config::$DB_CONFIG['port'];
        self::$_POS = $logPos;
        self::$_FILE = $logFile;

        if (false === (self::$_SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
        {
            throw new BinLogException(sprintf('Unable to create a socket: %s', socket_strerror(socket_last_error())));
        }
        socket_set_block(self::$_SOCKET);
        socket_set_option(self::$_SOCKET, SOL_SOCKET, SO_KEEPALIVE, 1);

        self::$_FLAG = ConstCapability::$CAPABILITIES;

        self::_connect();
    }

    /**
     * @throws BinLogException
     */
    private static function _connect()
    {
        if (false === socket_connect(self::$_SOCKET, self::$_HOST, self::$_PORT))
        {
            throw new BinLogException(socket_strerror(socket_last_error()), socket_last_error());
        }

        self::serverInfo();
        self::auth();
        self::getBinlogStream();
    }

    /**
     *
     */
    private static function serverInfo()
    {
        $pack = self::_readPacket();
        ServerInfo::run($pack);
        self::$_SALT = ServerInfo::getSalt();
    }

    /**
     * @return bool|string
     * @throws BinLogException
     */
    public static function _readPacket()
    {
        $header = self::_readBytes(4);
        if (false === $header)
        {
            return false;
        }
        $dataLength = unpack('L', $header[0] . $header[1] . $header[2] . chr(0))[1];

        return self::_readBytes($dataLength);
    }

    /**
     * @param $data_len
     * @return string
     * @throws BinLogException
     */
    private static function _readBytes($data_len)
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
                $resp = socket_read(self::$_SOCKET, $data_len - $bytes_read);
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
        } catch (\Exception $e)
        {
            throw new BinLogException(var_export($e, true));
        }
    }

    /**
     * @throws BinLogException
     */
    private static function auth()
    {
        $data = PackAuth::initPack(self::$_FLAG, self::$_USER, self::$_PASS, self::$_SALT);

        self::_write($data);
        $result = self::_readPacket();
        PackAuth::success($result);
    }

    /**
     * @param $data
     * @return bool
     * @throws BinLogException
     */
    private static function _write($data)
    {
        if (false === socket_write(self::$_SOCKET, $data, strlen($data)))
        {
            throw new BinLogException(sprintf('Unable to write to socket: %s', socket_strerror(socket_last_error())), socket_last_error());
        }
        return true;
    }

    /**
     * @throws BinLogException
     */
    public static function getBinlogStream()
    {
        // checksum
        self::$_CHECKSUM = DBHelper::isCheckSum();
        if (self::$_CHECKSUM)
        {
            self::execute('SET @master_binlog_checksum= @@global.binlog_checksum');
        }

        if ('' === self::$_GTID)
        {
            if ('' === self::$_POS || '' === self::$_FILE)
            {
                $master = DBHelper::getMasterStatus();
                self::$_POS = $master['Position'];
                self::$_FILE = $master['File'];
            }

            $prelude = pack('i', strlen(self::$_FILE) + 11) . chr(ConstCommand::COM_BINLOG_DUMP);
            $prelude .= pack('I', self::$_POS);
            $prelude .= pack('v', 0);
            $prelude .= pack('I', self::$_SLAVE_SERVER_ID);
            $prelude .= self::$_FILE;
        }
        else
        {
            $Gtid = new GtidSet(self::$_GTID);
            $encoded_data_size = $Gtid->encoded_length();

            $header_size =
                2 +  # binlog_flags
                4 +  # server_id
                4 +  # binlog_name_info_size
                4 +  # empty binlog name
                8 +  # binlog_pos_info_size
                4;

            $prelude = pack('l', $header_size + $encoded_data_size) . chr(ConstCommand::COM_BINLOG_DUMP_GTID);
            $prelude .= pack('S', 0);
            $prelude .= pack('I', self::$_SLAVE_SERVER_ID);
            $prelude .= pack('I', 3);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= chr(0);
            $prelude .= pack('Q', 4);

            $prelude .= pack('I', $Gtid->encoded_length());
            $prelude .= $Gtid->encoded();
        }

        self::_write($prelude);
        $result = self::_readPacket();
        PackAuth::success($result);
    }

    /**
     * @param $sql
     * @throws BinLogException
     */
    public static function execute($sql)
    {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC', $chunk_size, 0x03);
        self::_write($prelude . $sql);
    }

    /**
     * @return bool
     */
    public static function getCheckSum()
    {
        return self::$_CHECKSUM;
    }

    /**
     *
     */
    public function __destruct()
    {
        socket_shutdown(self::$_SOCKET);
        socket_close(self::$_SOCKET);
        self::$_SOCKET = null;
    }
}
