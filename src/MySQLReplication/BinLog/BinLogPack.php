<?php

namespace MySQLReplication\BinLog;

use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstMy;
use MySQLReplication\Exception\BinLogException;
use MySQLReplication\Pack\RowEvent;

/**
 * Class BinLogPack
 */
class BinLogPack
{
    /**
     * @var []
     */
    public static $EVENT_INFO;
    /**
     * @var  int
     */
    public static $EVENT_TYPE;
    /**
     * @var int
     */
    private static $_PACK_KEY = 0;
    /**
     * @var string
     */
    private static $_PACK;
    /**
     * @var string
     */
    private static $_FILE_NAME;
    /**
     * @var int
     */
    private static $_POS;
    /**
     * @var string gtid
     */
    private static $_GTID;

    /**
     * @param $pack
     * @param bool|true $checkSum
     * @param array $ignoredEvents
     * @param array $onlyTables
     * @param array $onlyDatabases
     * @return array
     */
    public function init(
        $pack,
        $checkSum = true,
        array $ignoredEvents = [],
        array $onlyTables = [],
        array $onlyDatabases = [])
    {
        self::$_PACK = $pack;
        self::$_PACK_KEY = 0;
        self::$EVENT_INFO = [];

        // ok value on first
        $this->advance(1);

        self::$EVENT_INFO = unpack('Vtime/Ctype/Vid/Vsize/Vpos/vflag', $this->read(19));

        self::$EVENT_TYPE = self::$EVENT_INFO['type'];
        $event_size = self::$EVENT_INFO['size'];

        $event_size_without_header = $checkSum === true ? ($event_size - 23) : ($event_size - 19);

        $data = [];

        if (in_array(self::$EVENT_TYPE, $ignoredEvents))
        {
            return $data;
        }

        if (self::$EVENT_TYPE == ConstEventType::TABLE_MAP_EVENT)
        {
            $data = RowEvent::tableMap($this, self::$EVENT_TYPE);
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::updateRow($this, self::$EVENT_TYPE, $event_size_without_header, $onlyTables, $onlyDatabases);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::addRow($this, self::$EVENT_TYPE, $event_size_without_header, $onlyTables, $onlyDatabases);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::delRow($this, self::$EVENT_TYPE, $event_size_without_header, $onlyTables, $onlyDatabases);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (self::$EVENT_TYPE == ConstEventType::XID_EVENT)
        {
            $data['xid'] = unpack('P', $this->read(8))[1];
        }
        elseif (self::$EVENT_TYPE == ConstEventType::ROTATE_EVENT)
        {
            self::$_POS = $this->readUInt64();
            self::$_FILE_NAME = $this->read($event_size_without_header - 8);

            $data['rotate'] = ['position' => self::$_POS, 'next_binlog' => self::$_FILE_NAME ];
        }
        elseif (self::$EVENT_TYPE == ConstEventType::GTID_LOG_EVENT)
        {
            //gtid event
            $commit_flag = unpack('C', $this->read(1))[1] == 1;
            $sid = unpack('H*', $this->read(16))[1];
            $gno = unpack('P', $this->read(8))[1];

            // GTID_NEXT
            self::$_GTID = vsprintf('%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s', str_split($sid)) . ':' . $gno;

            $data['gtid_log_event'] = ['commit_flag' => $commit_flag, 'sid' => $sid, 'gno' => $gno, 'gtid' => self::$_GTID];
        }
        else if (self::$EVENT_TYPE == ConstEventType::QUERY_EVENT)
        {
            $slave_proxy_id = $this->readUInt32();
            $execution_time = $this->readUInt32();
            $schema_length = $this->readUInt8();
            $error_code = $this->readUInt16();
            $status_vars_length = $this->readUInt16();

            $status_vars = $this->read($status_vars_length);
            $schema = $this->read($schema_length);
            $this->advance(1);

            $query = $this->read($event_size - 36 - $status_vars_length - $schema_length - 1);

            $data['query_event'] = [
                'slave_proxy_id' => $slave_proxy_id,
                'execution_time' => $execution_time,
                'schema' => $schema,
                'error_code' => $error_code,
                'query' => $query,
            ];
        }

        $data['event'] = self::$EVENT_INFO;

        return $data;
    }

    /**
     * @param $length
     */
    public function advance($length)
    {
        $this->read($length);
    }

    /**
     * @param int $length
     * @return string
     * @throws BinLogException
     */
    public function read($length)
    {
        $length = (int)$length;
        $return = substr(self::$_PACK,0,$length);
        self::$_PACK_KEY += $length;
        self::$_PACK = substr(self::$_PACK, $length);
        return $return;
    }

    /**
     * Push again data in data buffer. It's use when you want
     * to extract a bit from a value a let the rest of the code normally
     * read the data
     *
     * @param string $data
     */
    public function unread($data)
    {
        self::$_PACK_KEY -= strlen($data);
        self::$_PACK = $data . self::$_PACK;
    }

    /**
     * @return mixed
     */
    public function readUInt64()
    {
        $data = unpack('V*', $this->read(8));
        return bcadd($data[1], bcmul($data[2], bcpow(2, 32)));
    }

    /**
     * @see read a 'Length Coded Binary' number from the data buffer.
     * Length coded numbers can be anywhere from 1 to 9 bytes depending
     * on the value of the first byte.
     * From PyMYSQL source code
     *
     * @return int|string
     */
    public function readCodedBinary()
    {
        $c = ord($this->read(1));
        if ($c == ConstMy::NULL_COLUMN)
        {
            return '';
        }
        if ($c < ConstMy::UNSIGNED_CHAR_COLUMN)
        {
            return $c;
        }
        elseif ($c == ConstMy::UNSIGNED_SHORT_COLUMN)
        {
            return $this->readUInt16();

        }
        elseif ($c == ConstMy::UNSIGNED_INT24_COLUMN)
        {
            return $this->readUInt24();
        }
        elseif ($c == ConstMy::UNSIGNED_INT64_COLUMN)
        {
            return $this->readUInt64();
        }
        return $c;
    }

    /**
     * @return int
     */
    public function readInt24()
    {
        $data = unpack('C3', $this->read(3));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
        {
            $res -= 0x1000000;
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function readInt64()
    {
        return unpack('q', $this->read(8))[1];
    }

    /**
     * @param $size
     * @return string
     * @throws BinLogException
     */
    public function read_length_coded_pascal_string($size)
    {
        $length = $this->read_uint_by_size($size);
        return $this->read($length);
    }

    /**
     * Read a little endian integer values based on byte number
     * @param $size
     * @return mixed
     * @throws BinLogException
     */
    public function read_uint_by_size($size)
    {
        if ($size == 1)
        {
            return $this->readUInt8();
        }
        elseif ($size == 2)
        {
            return $this->readUInt16();
        }
        elseif ($size == 3)
        {
            return $this->readUInt24();
        }
        elseif ($size == 4)
        {
            return $this->readUInt32();
        }
        elseif ($size == 5)
        {
            return $this->readUInt40();
        }
        elseif ($size == 6)
        {
            return $this->readUInt48();
        }
        elseif ($size == 7)
        {
            return $this->readUInt56();
        }
        elseif ($size == 8)
        {
            return $this->readUInt64();
        }

        throw new BinLogException('$size ' . $size . ' not handled');
    }

    /**
     * @return mixed
     */
    public function readUInt8()
    {
        return unpack('C', $this->read(1))[1];
    }

    /**
     * @return mixed
     */
    public function readUInt16()
    {
        return unpack('v', $this->read(2))[1];
    }

    /**
     * @return mixed
     */
    public function readUInt24()
    {
        $data = unpack('C3', $this->read(3));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    /**
     * @return mixed
     */
    public function readUInt32()
    {
        return unpack('I', $this->read(4))[1];
    }

    /**
     * @return mixed
     */
    public function readUInt40()
    {
        $data = unpack('CI', $this->read(5));
        return $data[1] + ($data[2] << 8);
    }

    /**
     * @return mixed
     */
    public function readUInt48()
    {
        $data = unpack('v3', $this->read(6));
        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    /**
     * @return mixed
     */
    public function readUInt56()
    {
        $data = unpack('CSI', $this->read(7));
        return $data[1] + ($data[2] << 8) + ($data[3] << 24);
    }

    /**
     * Read a big endian integer values based on byte number
     * @param $size
     * @return int
     * @throws BinLogException
     */
    public function read_int_be_by_size($size)
    {
        if ($size == 1)
        {
            return unpack('c', $this->read($size))[1];
        }
        elseif ($size == 2)
        {
            return unpack('n', $this->read($size))[1];
        }
        elseif ($size == 3)
        {
            return $this->read_int24_be();
        }
        elseif ($size == 4)
        {
            return unpack('i', implode('',array_reverse(str_split($this->read(4)))))[1];
        }
        elseif ($size == 5)
        {
            return $this->read_int40_be();
        }
        elseif ($size == 8)
        {
            return unpack('l', $this->read($size))[1];
        }

        throw new BinLogException('$size ' . $size . ' not handled');
    }

    /**
     * @return int
     */
    public function read_int24_be()
    {
        $data = unpack('C3', $this->read(3));
        $res = ($data[1] << 16) | ($data[2] << 8) | $data[3];
        if ($res >= 0x800000)
        {
            $res -= 0x1000000;
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function read_int40_be()
    {
        $data = unpack('NC', $this->read(5));
        return $data[2] + ($data[1] << 8);
    }

    /**
     * @param $size
     * @return bool
     */
    public function isComplete($size)
    {
        if (self::$_PACK_KEY + 1 - 20 < $size)
        {
            return false;
        }
        return true;
    }
}
