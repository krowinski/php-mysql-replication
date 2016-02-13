<?php

require_once ROOT . 'config/Config.php';
require_once ROOT . 'db/TimeDate.php';
require_once ROOT . 'db/DBMysql.php';
require_once ROOT . 'db/DBHelper.php';
require_once ROOT . 'db/Log.php';
require_once ROOT . 'const/ConstFieldType.php';
require_once ROOT . 'bin/BinLogPack.php';
require_once ROOT . 'bin/BinLogEvent.php';
require_once ROOT . 'pack/RowEvent.php';
require_once ROOT . 'const/ConstEventType.php';
require_once ROOT . 'bin/BinLogColumns.php';
require_once ROOT . 'const/ConstCommand.php';
require_once ROOT . 'const/ConstAuth.php';
require_once ROOT . 'pack/ServerInfo.php';
require_once ROOT . 'pack/Gtid.php';
require_once ROOT . 'const/ConstCapability.php';
require_once ROOT . 'pack/PackAuth.php';
require_once ROOT . 'const/ConstMy.php';


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
    private static $_DB;
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
    private static $_SLAVE_SERVER_ID = 3;
    /**
     * @var bool
     */
    private static $_CHECKSUM = false;

    /**
     * @param bool|false $gtid
     * @param string $slave_id
     * @throws BinLogException
     */
    public static function init($gtid = false, $slave_id = '')
    {
        self::$_SLAVE_SERVER_ID = empty($slave_id) ? self::$_SLAVE_SERVER_ID : $slave_id;
        self::$_GTID = $gtid;
        self::$_HOST = Config::$DB_CONFIG['host'];
        self::$_USER = Config::$DB_CONFIG['username'];
        self::$_PASS = Config::$DB_CONFIG['password'];
        self::$_PORT = Config::$DB_CONFIG['port'];
        self::$_DB = Config::$DB_CONFIG['db_name'];


        if ((self::$_SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false)
        {
            throw new BinLogException(sprintf('Unable to create a socket: %s', socket_strerror(socket_last_error())));
        }
        socket_set_block(self::$_SOCKET);
        socket_set_option(self::$_SOCKET, SOL_SOCKET, SO_KEEPALIVE, 1);

        self::$_FLAG = ConstCapability::$CAPABILITIES;
        if (self::$_DB)
        {
            self::$_FLAG |= ConstCapability::$CONNECT_WITH_DB;
        }

        self::_connect();
    }


    /**
     * @throws BinLogException
     */
    private static function _connect()
    {
        if (!socket_connect(self::$_SOCKET, self::$_HOST, self::$_PORT))
        {
            throw new BinLogException(
                sprintf(
                    'error:%s, msg:%s',
                    socket_last_error(),
                    socket_strerror(socket_last_error())
                )
            );
        }

        self::_serverInfo();
        self::auth();
        self::getBinlogStream();
    }

    /**
     * @brief 获取ServerInfo
     * @return void
     */
    private static function _serverInfo()
    {
        $pack = self::_readPacket();
        ServerInfo::run($pack);
        self::$_SALT = ServerInfo::getSalt();
    }

    /**
     * @return bool|string
     * @throws BinLogException
     */
    private static function _readPacket()
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

                //
                if ($resp === false)
                {
                    self::_goneAway('remote host has closed the connection');
                    throw new BinLogException(
                        sprintf(
                            'remote host has closed. error:%s, msg:%s',
                            socket_last_error(),
                            socket_strerror(socket_last_error())
                        ));
                }

                // server kill connection or server gone away
                if (strlen($resp) === 0)
                {
                    self::_goneAway('read less data');
                    throw new BinLogException('read less ' . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if (strlen($body) < $data_len)
            {
                self::_goneAway('read undone data');
                throw new BinLogException('read less ' . ($data_len - strlen($body)));
            }
            return $body;
        } catch (Exception $e)
        {
            self::_goneAway('socekt read fail!');
            throw new BinLogException(var_export($e, true));
        }

    }

    /**
     * @param $msg
     */
    private static function _goneAway($msg)
    {
        Log::error($msg . 'mysql server has gone away', 'mysqlBinlog', Config::$LOG['binlog']['error']);
    }

    /**
     * @throws BinLogException
     */
    private static function auth()
    {
        $data = PackAuth::initPack(self::$_FLAG, self::$_USER, self::$_PASS, self::$_SALT, self::$_DB);

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
            throw new BinLogException(sprintf('Unable to write to socket: %s', socket_strerror(socket_last_error())));
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
            self::execute('set @master_binlog_checksum= @@global.binlog_checksum');
        }

        // self::_writeRegisterSlaveCommand();

        /*
        $header   = pack('l', 11 + strlen(self::$_FILE));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', self::$_POS);
        $data .= pack('s', 0);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= self::$_FILE;
        */

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
     * @throws BinLogException
     */
    private static function _writeRegisterSlaveCommand()
    {
        $header = pack('l', 18);

        // COM_BINLOG_DUMP
        $data = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
        $data .= pack('L', self::$_SLAVE_SERVER_ID);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        self::_write($data);

        $result = self::_readPacket();
        PackAuth::success($result);
    }

    /**
     * @return array
     */
    public static function analysisBinLog()
    {
        $pack = self::_readPacket();
        PackAuth::success($pack);

        $binlog = BinLogPack::getInstance();
        $result = $binlog->init($pack, self::$_CHECKSUM);

        self::$_GTID = $binlog->getGtid();

        if (DEBUG)
        {
            Log::out(round(memory_get_usage() / 1024 / 1024, 2) . 'MB');
        }

        if ($result)
        {
            return $result;
        }
        return null;
    }

    /**
     * @return int
     */
    public static function putFilePos()
    {
        list($filename, $pos) = BinLogPack::getFilePos();
        $data = sprintf('%s|%s|%s', $filename, $pos, date('Y-m-d H:i:s'));
        return file_put_contents(self::$FILE_POS, $data);
    }

    public function __destruct()
    {
        var_dump('sockettttsss');
        socket_shutdown(self::$_SOCKET);
        socket_close(self::$_SOCKET);
        self::$_SOCKET = null;
        die;
    }
}
