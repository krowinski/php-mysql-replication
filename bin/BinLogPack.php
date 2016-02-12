<?php

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
     * @var null
     */
    private static $_instance = null;
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
     * @return array
     */
    public static function getFilePos()
    {
        return [self::$_FILE_NAME, self::$_POS];
    }

    /**
     * @param $pack
     * @param bool|true $checkSum
     * @return array
     */
    public function init($pack, $checkSum = true)
    {
        if (!self::$_instance)
        {
            self::$_instance = new self();
        }

        //
        self::$_PACK = $pack;
        self::$_PACK_KEY = 0;
        self::$EVENT_INFO = [];


        $this->advance(1);

        self::$EVENT_INFO['time'] = $timestamp = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['type'] = self::$EVENT_TYPE = unpack('C', $this->read(1))[1];
        self::$EVENT_INFO['id'] = $server_id = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['size'] = $event_size = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['pos'] = $log_pos = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['flag'] = $flags = unpack('S', $this->read(2))[1];

        $event_size_without_header = $checkSum === true ? ($event_size - 23) : $event_size - 19;

        $data = [];

        if (self::$EVENT_TYPE == ConstEventType::TABLE_MAP_EVENT)
        {
            RowEvent::tableMap(self::getInstance(), self::$EVENT_TYPE);
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::updateRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::addRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (in_array(self::$EVENT_TYPE, [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2]))
        {
            $data = RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }
        elseif (self::$EVENT_TYPE == ConstEventType::XID_EVENT)
        {
            //var_dump(bin2hex($pack),$this->readUint64());
            //return RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE);
        }
        elseif (self::$EVENT_TYPE == ConstEventType::ROTATE_EVENT)
        {
            $log_pos = $this->readUInt64();
            self::$_FILE_NAME = $this->read($event_size_without_header - 8);
        }
        elseif (self::$EVENT_TYPE == ConstEventType::GTID_LOG_EVENT)
        {
            //gtid event
            $commit_flag = unpack('C', $this->read(1))[1] == 1;
            $sid = unpack('H*', $this->read(16))[1];
            $gno = unpack('Q', $this->read(8))[1];

            // GTID_NEXT
            self::$_GTID = vsprintf("%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s", str_split($sid)) . ':' . $gno;
        }
        elseif (self::$EVENT_TYPE == ConstEventType::FORMAT_DESCRIPTION_EVENT)
        {

        }
        elseif (self::$EVENT_TYPE == ConstEventType::QUERY_EVENT)
        {

        }

        if (DEBUG)
        {
            $msg = self::$_FILE_NAME;
            $msg .= '-- next pos -> ' . $log_pos;
            $msg .= '-- typeEvent -> ' . self::$EVENT_TYPE;
            $msg .= '-- gtid next -> ' . self::$_GTID;
            Log::out($msg);
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getGtid()
    {
        return self::$_GTID;
    }

    /**
     * @param $length
     */
    public function advance($length)
    {
        $this->read($length);
    }

    /**
     * @param $length
     * @return string
     */
    public function read($length)
    {
        $length = (int)$length;
        $n = '';
        for ($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++)
        {
            $n .= self::$_PACK[$i];
        }
        self::$_PACK_KEY += $length;

        return $n;
    }

    /**
     * @return BinLogPack|null
     */
    public static function getInstance()
    {
        if (!self::$_instance)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return mixed
     */
    public function readUInt64()
    {
        return unpack('Q', $this->read(8))[1];
    }

    /**
     * @brief read a 'Length Coded Binary' number from the data buffer.
     ** Length coded numbers can be anywhere from 1 to 9 bytes depending
     ** on the value of the first byte.
     ** From PyMYSQL source code
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
            return $this->unpackUInt16($this->read(ConstMy::UNSIGNED_SHORT_LENGTH));

        }
        elseif ($c == ConstMy::UNSIGNED_INT24_COLUMN)
        {
            return $this->unpackInt24($this->read(ConstMy::UNSIGNED_INT24_LENGTH));
        }
        elseif ($c == ConstMy::UNSIGNED_INT64_COLUMN)
        {
            return $this->unpackInt64($this->read(ConstMy::UNSIGNED_INT64_LENGTH));
        }
        return $c;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function unpackUInt16($data)
    {
        return unpack("S", $data[0] . $data[1])[1];
    }

    /**
     * @param $data
     * @return int
     */
    public function unpackInt24($data)
    {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        return $a;
    }

    /**
     * @param $data
     * @return int
     */
    public function unpackInt64($data)
    {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        $a += (int)((ord($data[3]) & 0xFF) << 24);
        $a += (int)((ord($data[4]) & 0xFF) << 32);
        $a += (int)((ord($data[5]) & 0xFF) << 40);
        $a += (int)((ord($data[6]) & 0xFF) << 48);
        $a += (int)((ord($data[7]) & 0xFF) << 56);
        return $a;
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
        return $this->readUInt64();
    }

    /**
     * @param $size
     * @return string
     * @throws Exception
     */
    public function read_length_coded_pascal_string($size)
    {
        $length = $this->read_uint_by_size($size);
        return $this->read($length);
    }

    /**
     * @param $size
     * @return mixed
     * @throws Exception
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

        throw new Exception('$size ' . $size . ' not handled');
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
        return unpack('S', $this->read(2))[1];
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
     * @param $size
     * @return int
     * @throws Exception
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
            return unpack('N', $this->read($size))[1];
        }
        elseif ($size == 5)
        {
            return $this->read_int40_be();
        }
        elseif ($size == 8)
        {
            return unpack('N', $this->read($size))[1];
        }

        throw new Exception('$size ' . $size . ' not handled');
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
        $data1 = unpack("N", $this->read(4))[1];
        $data2 = unpack("C", $this->read(1))[1];
        return $data2 + ($data1 << 8);
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
