<?php

namespace MySQLReplication\BinaryDataReader;

use MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException;

/**
 * Class BinaryDataReader
 * @package MySQLReplication\BinaryDataReader
 */
class BinaryDataReader
{
    const NULL_COLUMN = 251;
    const UNSIGNED_CHAR_COLUMN = 251;
    const UNSIGNED_SHORT_COLUMN = 252;
    const UNSIGNED_INT24_COLUMN = 253;
    const UNSIGNED_INT64_COLUMN = 254;
    const UNSIGNED_CHAR_LENGTH = 1;
    const UNSIGNED_SHORT_LENGTH = 2;
    const UNSIGNED_INT24_LENGTH = 3;
    const UNSIGNED_INT32_LENGTH = 4;
    const UNSIGNED_FLOAT_LENGTH = 4;
    const UNSIGNED_DOUBLE_LENGTH = 8;
    const UNSIGNED_INT40_LENGTH = 5;
    const UNSIGNED_INT48_LENGTH = 6;
    const UNSIGNED_INT56_LENGTH = 7;
    const UNSIGNED_INT64_LENGTH = 8;

    /**
     * @var int
     */
    private $readBytes = 0;
    /**
     * @var string
     */
    private $binaryData = '';

    /**
     * Package constructor.
     * @param string $binaryData
     */
    public function __construct($binaryData)
    {
        $this->binaryData = $binaryData;
    }

    /**
     * @param int $length
     */
    public function advance($length)
    {
        $length = (int)$length;
        $this->readBytes += $length;
        $this->binaryData = substr($this->binaryData, $length);
    }

    /**
     * @param int $length
     * @return string
     * @throws BinaryDataReaderException
     */
    public function read($length)
    {
        $length = (int)$length;
        $return = substr($this->binaryData, 0, $length);
        $this->readBytes += $length;
        $this->binaryData = substr($this->binaryData, $length);

        return $return;
    }

    /**
     * @return int
     */
    public function readInt16()
    {
        return unpack('s', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
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
        $this->readBytes -= strlen($data);
        $this->binaryData = $data . $this->binaryData;
    }

    /**
     * Read a 'Length Coded Binary' number from the data buffer.
     * Length coded numbers can be anywhere from 1 to 9 bytes depending
     * on the value of the first byte.
     * From PyMYSQL source code
     * 
     * @return int|string
     * @throws BinaryDataReaderException
     */
    public function readCodedBinary()
    {
        $c = ord($this->read(self::UNSIGNED_CHAR_LENGTH));
        if ($c == self::NULL_COLUMN)
        {
            return '';
        }
        if ($c < self::UNSIGNED_CHAR_COLUMN)
        {
            return $c;
        }
        elseif ($c == self::UNSIGNED_SHORT_COLUMN)
        {
            return $this->readUInt16();

        }
        elseif ($c == self::UNSIGNED_INT24_COLUMN)
        {
            return $this->readUInt24();
        }
        elseif ($c == self::UNSIGNED_INT64_COLUMN)
        {
            return $this->readUInt64();
        }

        throw new BinaryDataReaderException('Column num ' . $c . ' not handled');
    }

    /**
     * @return int
     */
    public function readUInt16()
    {
        return unpack('v', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
    }

    /**
     * @return int
     */
    public function readUInt24()
    {
        $data = unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    /**
     * @return int
     */
    public function readUInt64()
    {
        return $this->unpackUInt64($this->read(self::UNSIGNED_INT64_LENGTH));
    }

    /**
     * @param string $data
     * @return string
     */
    public function unpackUInt64($data)
    {
        $data = unpack('V*', $data);
        return bcadd($data[1], bcmul($data[2], bcpow(2, 32)));
    }

    /**
     * @return int
     */
    public function readInt24()
    {
        $data = unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
        {
            $res -= 0x1000000;
        }
        return $res;
    }

    /**
     * @return string
     */
    public function readInt64()
    {
        $data = unpack('V*', $this->read(self::UNSIGNED_INT64_LENGTH));
        return bcadd($data[1], $data[2] << 32);
    }

    /**
     * @param int $size
     * @return string
     * @throws BinaryDataReaderException
     */
    public function readLengthCodedPascalString($size)
    {
        return $this->read($this->readUIntBySize($size));
    }

    /**
     * Read a little endian integer values based on byte number
     *
     * @param $size
     * @return mixed
     * @throws BinaryDataReaderException
     */
    public function readUIntBySize($size)
    {
        if ($size == self::UNSIGNED_CHAR_LENGTH)
        {
            return $this->readUInt8();
        }
        elseif ($size == self::UNSIGNED_SHORT_LENGTH)
        {
            return $this->readUInt16();
        }
        elseif ($size == self::UNSIGNED_INT24_LENGTH)
        {
            return $this->readUInt24();
        }
        elseif ($size == self::UNSIGNED_INT32_LENGTH)
        {
            return $this->readUInt32();
        }
        elseif ($size == self::UNSIGNED_INT40_LENGTH)
        {
            return $this->readUInt40();
        }
        elseif ($size == self::UNSIGNED_INT48_LENGTH)
        {
            return $this->readUInt48();
        }
        elseif ($size == self::UNSIGNED_INT56_LENGTH)
        {
            return $this->readUInt56();
        }
        elseif ($size == self::UNSIGNED_INT64_LENGTH)
        {
            return $this->readUInt64();
        }

        throw new BinaryDataReaderException('$size ' . $size . ' not handled');
    }

    /**
     * @return int
     */
    public function readUInt8()
    {
        return unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
    }

    /**
     * @return int
     */
    public function readUInt32()
    {
        return unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
    }

    /**
     * @return mixed
     */
    public function readUInt40()
    {
        $data1 = unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
        $data2 = unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];

        return $data1 + ($data2 << 8);
    }

    /**
     * @return mixed
     */
    public function readUInt48()
    {
        $data = unpack('v3', $this->read(self::UNSIGNED_INT48_LENGTH));

        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    /**
     * @return mixed
     */
    public function readUInt56()
    {
        $data1 = unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
        $data2 = unpack('S', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
        $data3 = unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];

        return $data1 + ($data2 << 8) + ($data3 << 24);
    }

    /**
     * Read a big endian integer values based on byte number
     *
     * @param int $size
     * @return int
     * @throws BinaryDataReaderException
     */
    public function readIntBeBySize($size)
    {
        if ($size == self::UNSIGNED_CHAR_LENGTH)
        {
            return $this->readInt8();
        }
        elseif ($size == self::UNSIGNED_SHORT_LENGTH)
        {
            return $this->readInt16Be();
        }
        elseif ($size == self::UNSIGNED_INT24_LENGTH)
        {
            return $this->readInt24Be();
        }
        elseif ($size == self::UNSIGNED_INT32_LENGTH)
        {
            return $this->readInt32Be();
        }
        elseif ($size == self::UNSIGNED_INT40_LENGTH)
        {
            return $this->readInt40Be();
        }

        throw new BinaryDataReaderException('$size ' . $size . ' not handled');
    }

    /**
     * @return int
     */
    public function readInt8()
    {
        return unpack('c', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
    }

    /**
     * @return mixed
     */
    public function readInt16Be()
    {
        return unpack('n', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
    }

    /**
     * @return int
     */
    public function readInt24Be()
    {
        $data = unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));
        $res = ($data[1] << 16) | ($data[2] << 8) | $data[3];
        if ($res >= 0x800000)
        {
            $res -= 0x1000000;
        }

        return $res;
    }

    /**
     * @return int
     */
    public function readInt32Be()
    {
        return unpack('i', strrev($this->read(self::UNSIGNED_INT32_LENGTH)))[1];
    }

    /**
     * @return int
     */
    public function readInt40Be()
    {
        $data1 = unpack('N', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
        $data2 = unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];

        return $data2 + ($data1 << 8);
    }

    /**
     * @return int
     */
    public function readInt32()
    {
        return unpack('i', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
    }

    /**
     * @return float
     */
    public function readFloat()
    {
        return unpack('f', $this->read(self::UNSIGNED_FLOAT_LENGTH))[1];
    }

    /**
     * @return double
     */
    public function readDouble()
    {
        return unpack('d', $this->read(self::UNSIGNED_DOUBLE_LENGTH))[1];
    }

    /**
     * @return string
     */
    public function readTableId()
    {
        return $this->unpackUInt64($this->read(self::UNSIGNED_INT48_LENGTH) . chr(0) . chr(0));
    }

    /**
     * @param int $size
     * @return bool
     */
    public function isComplete($size)
    {
        if ($this->readBytes + 1 - 20 < $size)
        {
            return false;
        }
        return true;
    }

    /**
     * @param int $value
     * @return string
     */
    public static function pack64bit($value)
    {
        return pack('C8', ($value >> 0) & 0xFF, ($value >> 8) & 0xFF, ($value >> 16) & 0xFF, ($value >> 24) & 0xFF, ($value >> 32) & 0xFF, ($value >> 40) & 0xFF, ($value >> 48) & 0xFF, ($value >> 56) & 0xFF);
    }
}