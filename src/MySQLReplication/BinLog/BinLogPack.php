<?php

namespace MySQLReplication\BinLog;

use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstMy;
use MySQLReplication\DTO\DeleteRowsDTO;
use MySQLReplication\DTO\EventDTO;
use MySQLReplication\DTO\GTIDLogDTO;
use MySQLReplication\DTO\QueryDTO;
use MySQLReplication\DTO\RotateDTO;
use MySQLReplication\DTO\TableMapDTO;
use MySQLReplication\DTO\UpdateRowsDTO;
use MySQLReplication\DTO\WriteRowsDTO;
use MySQLReplication\DTO\XidDTO;
use MySQLReplication\Exception\BinLogException;
use MySQLReplication\Pack\RowEvent;

/**
 * Class BinLogPack
 */
class BinLogPack
{
    /**
     * @var array
     */
    private $eventInfo;
    /**
     * @var int
     */
    private $readBytes = 0;
    /**
     * @var string
     */
    private $buffer = '';
    /**
     * @var DBHelper
     */
    private $DBHelper;

    public function __construct(DBHelper $DBHelper)
    {
        $this->DBHelper = $DBHelper;
    }

    /**
     * @param $pack
     * @param bool|true $checkSum
     * @param array $onlyEvents
     * @param array $ignoredEvents
     * @param array $onlyTables
     * @param array $onlyDatabases
     * @return WriteRowsDTO|UpdateRowsDTO|DeleteRowsDTO|XidDTO|EventDTO|QueryDTO|GTIDLogDTO|RotateDTO|TableMapDTO
     */
    public function init(
        $pack,
        $checkSum = true,
        array $onlyEvents = [],
        array $ignoredEvents = [],
        array $onlyTables = [],
        array $onlyDatabases = []
    ) {
        $this->buffer = $pack;
        $this->readBytes = 0;
        $this->eventInfo = [];

        // "ok" value on first byte
        $this->advance(1);

        $this->eventInfo = unpack('Vtime/Ctype/Vid/Vsize/Vpos/vflag', $this->read(19));
        $this->eventInfo['date'] = (new \DateTime())->setTimestamp($this->eventInfo['time'])->format('c');

        $event_size_without_header = true === $checkSum ? ($this->eventInfo['size'] - 23) : ($this->eventInfo['size'] - 19);

        if ($this->eventInfo['type'] == ConstEventType::TABLE_MAP_EVENT)
        {
            return RowEvent::tableMap($this, $this->DBHelper, $this->eventInfo, $event_size_without_header);
        }

        if (!empty($onlyEvents) && !in_array($this->eventInfo['type'], $onlyEvents))
        {
            return null;
        }

        if (in_array($this->eventInfo['type'], $ignoredEvents))
        {
            return null;
        }

        if (in_array($this->eventInfo['type'], [
            ConstEventType::UPDATE_ROWS_EVENT_V1,
            ConstEventType::UPDATE_ROWS_EVENT_V2
        ]))
        {
            return RowEvent::updateRow($this, $this->eventInfo, $event_size_without_header, $onlyTables, $onlyDatabases);
        }
        elseif (in_array($this->eventInfo['type'], [
            ConstEventType::WRITE_ROWS_EVENT_V1,
            ConstEventType::WRITE_ROWS_EVENT_V2
        ]))
        {
            return RowEvent::addRow($this, $this->eventInfo, $event_size_without_header, $onlyTables, $onlyDatabases);
        }
        elseif (in_array($this->eventInfo['type'], [
            ConstEventType::DELETE_ROWS_EVENT_V1,
            ConstEventType::DELETE_ROWS_EVENT_V2
        ]))
        {
            return RowEvent::delRow($this, $this->eventInfo, $event_size_without_header, $onlyTables, $onlyDatabases);
        }
        elseif ($this->eventInfo['type'] == ConstEventType::XID_EVENT)
        {
            return new XidDTO(
                $this->eventInfo['date'],
                $this->eventInfo['pos'],
                $this->eventInfo['size'],
                $event_size_without_header,
                $this->readUInt64()
            );
        }
        elseif ($this->eventInfo['type'] == ConstEventType::ROTATE_EVENT)
        {
            $pos = $this->readUInt64();
            $binFileName = $this->read($event_size_without_header - 8);

            return new RotateDTO(
                $this->eventInfo['date'],
                $this->eventInfo['pos'],
                $this->eventInfo['size'],
                $event_size_without_header,
                $pos,
                $binFileName
            );
        }
        elseif ($this->eventInfo['type'] == ConstEventType::GTID_LOG_EVENT)
        {
            //gtid event
            $commit_flag = $this->readUInt8() == 1;
            $sid = unpack('H*', $this->read(16))[1];
            $gno = $this->readUInt64();

            return new GTIDLogDTO(
                $this->eventInfo['date'],
                $this->eventInfo['pos'],
                $this->eventInfo['size'],
                $event_size_without_header,
                $commit_flag,
                vsprintf('%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s', str_split($sid)) . ':' . $gno
            );
        }
        else if ($this->eventInfo['type'] == ConstEventType::QUERY_EVENT)
        {
            $this->advance(4);
            $execution_time = $this->readUInt32();
            $schema_length = $this->readUInt8();
            $this->advance(2);
            $status_vars_length = $this->readUInt16();
            $this->advance($status_vars_length);
            $schema = $this->read($schema_length);
            $this->advance(1);
            $query = $this->read($this->eventInfo['size'] - 36 - $status_vars_length - $schema_length - 1);

            return new QueryDTO(
                $this->eventInfo['date'],
                $this->eventInfo['pos'],
                $this->eventInfo['size'],
                $event_size_without_header,
                $schema,
                $execution_time,
                $query
            );
        }

        return null;
    }

    /**
     * @param int $length
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
        $return = substr($this->buffer, 0, $length);
        $this->readBytes += $length;
        $this->buffer = substr($this->buffer, $length);
        return $return;
    }

    /**
     * @return int
     */
    public function readUInt64()
    {
        return $this->unpackUInt64($this->read(8));
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
    public function readUInt8()
    {
        return unpack('C', $this->read(1))[1];
    }

    /**
     * @return int
     */
    public function readUInt32()
    {
        return unpack('I', $this->read(4))[1];
    }

    /**
     * @return int
     */
    public function readUInt16()
    {
        return unpack('v', $this->read(2))[1];
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
        $this->buffer = $data . $this->buffer;
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
    public function readUInt24()
    {
        $data = unpack('C3', $this->read(3));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
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
     * @return string
     */
    public function readInt64()
    {
        $data = unpack('V*', $this->read(8));
        return bcadd($data[1], ($data[2] << 32));
    }

    /**
     * @param int $size
     * @return string
     * @throws BinLogException
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
     * @throws BinLogException
     */
    public function readUIntBySize($size)
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
     *
     * @param int $size
     * @return int
     * @throws BinLogException
     */
    public function readIntBeBySize($size)
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
            return $this->readInt24Be();
        }
        elseif ($size == 4)
        {
            return unpack('i', strrev($this->read(4)))[1];
        }
        elseif ($size == 5)
        {
            return $this->readInt40Be();
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
    public function readInt24Be()
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
     * @return int
     */
    public function readInt40Be()
    {
        $data1 = unpack('N', $this->read(4))[1];
        $data2 = unpack('C', $this->read(1))[1];
        return $data2 + ($data1 << 8);
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
}
