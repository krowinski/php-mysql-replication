<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinLog\BinLogColumns;
use MySQLReplication\Event\EventCommon;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Exception\BinLogException;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class RowEvent
 * @package MySQLReplication\RowEvent
 */
class RowEvent extends EventCommon
{
    /**
     * @var array
     */
    private $bitCountInByte = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        4, 5, 5, 6, 5, 6, 6, 7, 5, 6, 6, 7, 6, 7, 7, 8,
    ];
    /**
     * @var array
     */
    private static $tableMapCache;
    /**
     * @var string
     */
    private $tableMapTableName;
    /**
     * @var string
     */
    private $tableMapDatabase;
    /**
     * @var int
     */
    private $tableMapColumnsAmount;
    /**
     * @var array
     */
    private $tableMapFields;
    /**
     * @var bool
     */
    private $process = true;
    /**
     * @var MySQLRepository
     */
    private $MySQLRepository;
    /**
     * @var Config
     */
    private $config;

    /**
     * RowEvent constructor.
     * @param Config $config
     * @param MySQLRepository $MySQLRepository
     * @param BinaryDataReader $binaryDataReader
     * @param EventInfo $eventInfo
     */
    public function __construct(Config $config, MySQLRepository $MySQLRepository, BinaryDataReader $binaryDataReader, EventInfo $eventInfo)
    {
        parent::__construct($eventInfo,$binaryDataReader);

        $this->MySQLRepository = $MySQLRepository;
        $this->config = $config;
    }

    /**
     * This evenement describe the structure of a table.
     * It's send before a change append on a table.
     * A end user of the lib should have no usage of this
     *
     * @return TableMapDTO
     */
    public function makeTableMapDTO()
    {
        $tableId = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);

        $data = [];
        $data['schema_length'] = $this->binaryDataReader->readUInt8();
        $data['schema_name'] = $this->tableMapDatabase = $this->binaryDataReader->read($data['schema_length']);
        $this->binaryDataReader->advance(1);

        $data['table_length'] = $this->binaryDataReader->readUInt8();
        $data['table_name'] = $this->tableMapTableName = $this->binaryDataReader->read($data['table_length']);
        $this->binaryDataReader->advance(1);

        $this->tableMapColumnsAmount = $this->binaryDataReader->readCodedBinary();

        $column_type_def = $this->binaryDataReader->read($this->tableMapColumnsAmount);

        // automatically clear array to save memory
        if (count(self::$tableMapCache) >= 200)
        {
            self::$tableMapCache = array_slice(self::$tableMapCache, 100, -1, true);
        }

        $tableMapDTO = new TableMapDTO(
            $this->eventInfo,
            $tableId,
            $data['schema_name'],
            $data['table_name'],
            $this->tableMapColumnsAmount
        );

        if (isset(self::$tableMapCache[$tableId]))
        {
            return $tableMapDTO;
        }

        $this->binaryDataReader->readCodedBinary();

        self::$tableMapCache[$tableId]['fields'] = [];
        self::$tableMapCache[$tableId]['database'] = $data['schema_name'];
        self::$tableMapCache[$tableId]['table_name'] = $data['table_name'];

        $columns = $this->MySQLRepository->getFields($data['schema_name'], $data['table_name']);
        // if you drop tables and parse of logs you will get empty scheme
        if (empty($columns))
        {
            return $tableMapDTO;
        }

        for ($i = 0; $i < strlen($column_type_def); $i++)
        {
            // this a dirty hack to prevent row events containing columns which have been dropped
            if (!isset($columns[$i]))
            {
                $columns[$i] = [
                    'COLUMN_NAME' => 'DROPPED_COLUMN_' . $i,
                    'COLLATION_NAME' => null,
                    'CHARACTER_SET_NAME' => null,
                    'COLUMN_COMMENT' => null,
                    'COLUMN_TYPE' => 'BLOB',
                    'COLUMN_KEY' => '',
                ];
                $type = ConstFieldType::IGNORE;
            }
            else
            {
                $type = ord($column_type_def[$i]);
            }

            self::$tableMapCache[$tableId]['fields'][$i] = BinLogColumns::parse($type, $columns[$i], $this->binaryDataReader);
        }

        return $tableMapDTO;
    }

    /**
     * @return WriteRowsDTO
     */
    public function makeWriteRowsDTO()
    {
        $this->rowInit();

        if (false === $this->process)
        {
            return null;
        }

        $values = $this->getValues();

        return new WriteRowsDTO(
            $this->eventInfo,
            $this->tableMapDatabase,
            $this->tableMapTableName,
            $this->tableMapColumnsAmount,
            count($values),
            $values
        );
    }

    /**
     *
     */
    private function rowInit()
    {
        $this->process = true;

        $tableId = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);

        if (in_array($this->eventInfo->getType(), [
            ConstEventType::DELETE_ROWS_EVENT_V2,
            ConstEventType::WRITE_ROWS_EVENT_V2,
            ConstEventType::UPDATE_ROWS_EVENT_V2
        ]))
        {
            $this->binaryDataReader->read($this->binaryDataReader->readUInt16() / 8);
        }

        $this->tableMapColumnsAmount = $this->binaryDataReader->readCodedBinary();


        $this->tableMapFields = [];
        if (isset(self::$tableMapCache[$tableId]))
        {
            $this->tableMapFields = self::$tableMapCache[$tableId]['fields'];

            if ([] !== $this->config->getTablesOnly() && !in_array(self::$tableMapCache[$tableId]['table_name'], $this->config->getTablesOnly()))
            {
                $this->process = false;
            }

            if ([] !== $this->config->getDatabasesOnly() && !in_array(self::$tableMapCache[$tableId]['database'], $this->config->getDatabasesOnly()))
            {
                $this->process = false;
            }
        }
        if ([] == $this->tableMapFields)
        {
            //remove cache can be empty (drop table)
            unset(self::$tableMapCache[$tableId]);
            $this->process = false;
        }
    }

    /**
     * @param int $colsBitmap
     * @return array
     * @throws \Exception
     */
    private function getColumnData($colsBitmap)
    {
        $values = [];

        $l = $this->getColumnsBinarySize($this->bitCount($colsBitmap));

        // null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        // see http://dev.mysql.com/doc/internals/en/rows-event.html
        $null_bitmap = $this->binaryDataReader->read($l);
        $nullBitmapIndex = 0;

        foreach ($this->tableMapFields as $i => $column)
        {
            $name = $column['name'];
            $unsigned = $column['unsigned'];

            if ($this->bitGet($colsBitmap, $i) == 0)
            {
                $values[$name] = null;
                continue;
            }

            if ($this->checkNull($null_bitmap, $nullBitmapIndex))
            {
                $values[$name] = null;
            }
            elseif ($column['type'] == ConstFieldType::IGNORE)
            {
                $values[$name] = null;
            }
            elseif ($column['type'] == ConstFieldType::TINY)
            {
                if ($unsigned)
                {
                    $values[$name] = $this->binaryDataReader->readUInt8();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt8();
                }
            }
            elseif ($column['type'] == ConstFieldType::SHORT)
            {
                if ($unsigned)
                {
                    $values[$name] = $this->binaryDataReader->readUInt16();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt16();
                }
            }
            elseif ($column['type'] == ConstFieldType::LONG)
            {
                if ($unsigned)
                {
                    $values[$name] = $this->binaryDataReader->readUInt32();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt32();
                }
            }
            elseif ($column['type'] == ConstFieldType::LONGLONG)
            {
                if ($unsigned)
                {
                    $values[$name] = $this->binaryDataReader->readUInt64();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt64();
                }
            }
            elseif ($column['type'] == ConstFieldType::INT24)
            {
                if ($unsigned)
                {
                    $values[$name] = $this->binaryDataReader->readUInt24();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt24();
                }
            }
            elseif ($column['type'] == ConstFieldType::FLOAT)
            {
                // http://dev.mysql.com/doc/refman/5.7/en/floating-point-types.html FLOAT(7,4)
                $values[$name] = round($this->binaryDataReader->readFloat(), 4);
            }
            elseif ($column['type'] == ConstFieldType::DOUBLE)
            {
                $values[$name] = $this->binaryDataReader->readDouble();
            }
            elseif ($column['type'] == ConstFieldType::VARCHAR || $column['type'] == ConstFieldType::STRING)
            {
                if ($column['max_length'] > 255)
                {
                    $values[$name] = $this->getString(2, $column);
                }
                else
                {
                    $values[$name] = $this->getString(1, $column);
                }
            }
            elseif ($column['type'] == ConstFieldType::NEWDECIMAL)
            {
                $values[$name] = $this->getDecimal($column);
            }
            elseif ($column['type'] == ConstFieldType::BLOB)
            {
                $values[$name] = $this->getString($column['length_size'], $column);
            }
            elseif ($column['type'] == ConstFieldType::DATETIME)
            {
                $values[$name] = $this->getDatetime();
            }
            elseif ($column['type'] == ConstFieldType::DATETIME2)
            {
                $values[$name] = $this->getDatetime2($column);
            }
            elseif ($column['type'] == ConstFieldType::TIME2)
            {
                $values[$name] = $this->getTime2($column);
            }
            elseif ($column['type'] == ConstFieldType::TIMESTAMP2)
            {
                $values[$name] = $this->getTimestamp2($column);
            }
            elseif ($column['type'] == ConstFieldType::DATE)
            {
                $values[$name] = $this->getDate();
            }
            elseif ($column['type'] == ConstFieldType::YEAR)
            {
                $values[$name] = $this->binaryDataReader->readUInt8() + 1900;
            }
            elseif ($column['type'] == ConstFieldType::ENUM)
            {
                $values[$name] = $column['enum_values'][$this->binaryDataReader->readUIntBySize($column['size']) - 1];
            }
            elseif ($column['type'] == ConstFieldType::SET)
            {
                $values[$name] = $this->getSet($column);
            }
            elseif ($column['type'] == ConstFieldType::BIT)
            {
                $values[$name] = $this->getBit($column);
            }
            elseif ($column['type'] == ConstFieldType::GEOMETRY)
            {
                $values[$name] = $this->binaryDataReader->readLengthCodedPascalString($column['length_size']);
            }
            else
            {
                throw new BinLogException('Unknown row type: ' . $column['type']);
            }

            $nullBitmapIndex += 1;
        }

        return $values;
    }

    /**
     * @param int $columns
     * @return int
     */
    private function getColumnsBinarySize($columns)
    {
        return (int)(($columns + 7) / 8);
    }

    /**
     * @param string $bitmap
     * @return int
     */
    protected function bitCount($bitmap)
    {
        $n = 0;
        for ($i = 0; $i < strlen($bitmap); $i++)
        {
            $bit = $bitmap[$i];
            if (is_string($bit))
            {
                $bit = ord($bit);
            }
            $n += $this->bitCountInByte[$bit];
        }

        return $n;
    }

    /**
     * @param string $bitmap
     * @param int $position
     * @return int
     */
    private function bitGet($bitmap, $position)
    {
        $bit = $bitmap[(int)($position / 8)];
        if (is_string($bit))
        {
            $bit = ord($bit);
        }

        return $bit & (1 << ($position & 7));
    }

    /**
     * @param string $nullBitmap
     * @param int $position
     * @return int
     */
    private function checkNull($nullBitmap, $position)
    {
        $bit = $nullBitmap[intval($position / 8)];
        if (is_string($bit))
        {
            $bit = ord($bit);
        }

        return $bit & (1 << ($position % 8));
    }

    /**
     * @param int $size
     * @param array $column
     * @return string
     */
    private function getString($size, array $column)
    {
        $string = $this->binaryDataReader->readLengthCodedPascalString($size);
        if ($column['character_set_name'])
        {
            // convert strings?
        }

        return $string;
    }

    /**
     * Read MySQL's new decimal format introduced in MySQL 5
     * @param array $column
     * @return string
     */
    private function getDecimal(array $column)
    {
        $digits_per_integer = 9;
        $compressed_bytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
        $integral = $column['precision'] - $column['decimals'];
        $uncomp_integral = (int)($integral / $digits_per_integer);
        $uncomp_fractional = (int)($column['decimals'] / $digits_per_integer);
        $comp_integral = $integral - ($uncomp_integral * $digits_per_integer);
        $comp_fractional = $column['decimals'] - ($uncomp_fractional * $digits_per_integer);

        $value = $this->binaryDataReader->readUInt8();
        if (0 != ($value & 0x80))
        {
            $mask = 0;
            $res = '';
        }
        else
        {
            $mask = -1;
            $res = '-';
        }
        $this->binaryDataReader->unread(pack('C', ($value ^ 0x80)));

        $size = $compressed_bytes[$comp_integral];
        if ($size > 0)
        {
            $value = $this->binaryDataReader->readIntBeBySize($size) ^ $mask;
            $res .= $value;
        }

        for ($i = 0; $i < $uncomp_integral; $i++)
        {
            $value = $this->binaryDataReader->readInt32Be() ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $res .= '.';

        for ($i = 0; $i < $uncomp_fractional; $i++)
        {
            $value = $this->binaryDataReader->readInt32Be() ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $size = $compressed_bytes[$comp_fractional];
        if ($size > 0)
        {
            $value = $this->binaryDataReader->readIntBeBySize($size) ^ $mask;
            $res .= sprintf('%0' . $comp_fractional . 'd', $value);
        }

        return bcmul($res, 1, $column['precision']);
    }

    /**
     * @return float|null
     */
    private function getDatetime()
    {
        $value = $this->binaryDataReader->readUInt64();
        // nasty mysql 0000-00-00 dates
        if ($value == 0)
        {
            return null;
        }

        $date = $value / 1000000;
        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year == 0 || $month == 0 || $day == 0)
        {
            return null;
        }

        return (new \DateTime())->setDate($year, $month, $day)->format('Y-m-d');
    }

    /**
     * Date Time
     * 1 bit  sign           (1= non-negative, 0= negative)
     * 17 bits year*13+month  (year 0-9999, month 0-12)
     * 5 bits day            (0-31)
     * 5 bits hour           (0-23)
     * 6 bits minute         (0-59)
     * 6 bits second         (0-59)
     * ---------------------------
     * 40 bits = 5 bytes
     * @param array $column
     * @return string
     * @throws \Exception
     */
    private function getDatetime2(array $column)
    {
        $data = $this->binaryDataReader->readIntBeBySize(5);

        $year_month = $this->getBinarySlice($data, 1, 17, 40);

        $year = (int)($year_month / 13);
        $month = $year_month % 13;
        $day = $this->getBinarySlice($data, 18, 5, 40);
        $hour = $this->getBinarySlice($data, 23, 5, 40);
        $minute = $this->getBinarySlice($data, 28, 6, 40);
        $second = $this->getBinarySlice($data, 34, 6, 40);

        $date = new \DateTime($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);
        if (array_sum($date->getLastErrors()) > 0)
        {
            return null;
        }

        return $date->format('Y-m-d H:i:s') . $this->getFSP($column);
    }

    /**
     * Read a part of binary data and extract a number
     * binary: the data
     * start: From which bit (1 to X)
     * size: How many bits should be read
     * data_length: data size
     *
     * @param int $binary
     * @param int $start
     * @param int $size
     * @param int $data_length
     * @return int
     */
    private function getBinarySlice($binary, $start, $size, $data_length)
    {
        $binary = $binary >> $data_length - ($start + $size);
        $mask = ((1 << $size) - 1);

        return $binary & $mask;
    }

    /**
     * Read and add the fractional part of time
     * For more details about new date format:
     * http://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
     *
     * @param array $column
     * @return int|string
     * @throws \Exception
     */
    private function getFSP(array $column)
    {
        $read = 0;
        $time = '';
        if ($column['fsp'] == 1 || $column['fsp'] == 2)
        {
            $read = 1;
        }
        elseif ($column['fsp'] == 3 || $column['fsp'] == 4)
        {
            $read = 2;
        }
        elseif ($column ['fsp'] == 5 || $column['fsp'] == 6)
        {
            $read = 3;
        }
        if ($read > 0)
        {
            $microsecond = $this->binaryDataReader->readIntBeBySize($read);
            if ($column['fsp'] % 2)
            {
                $time = (int)($microsecond / 10);
            }
            else
            {
                $time = $microsecond;
            }
        }

        return $time;
    }

    /**
     * TIME encoding for nonfractional part:
     * 1 bit sign    (1= non-negative, 0= negative)
     * 1 bit unused  (reserved for future extensions)
     * 10 bits hour   (0-838)
     * 6 bits minute (0-59)
     * 6 bits second (0-59)
     * ---------------------
     * 24 bits = 3 bytes
     *
     * @param array $column
     * @return string
     */
    private function getTime2(array $column)
    {
        $data = $this->binaryDataReader->readInt24Be();

        $hour = $this->getBinarySlice($data, 2, 10, 24);
        $minute = $this->getBinarySlice($data, 12, 6, 24);
        $second = $this->getBinarySlice($data, 18, 6, 24);

        return (new \DateTime())->setTime($hour, $minute, $second)->format('H:i:s') . $this->getFSP($column);
    }

    /**
     * @param array $column
     * @return bool|string
     * @throws BinLogException
     */
    private function getTimestamp2(array $column)
    {
        $time = date('Y-m-d H:i:s', $this->binaryDataReader->readInt32Be());
        $fsp = $this->getFSP($column);
        if ('' !== $fsp)
        {
            $time .= '.' . $fsp;
        }
        return $time;
    }

    /**
     * @return string
     */
    private function getDate()
    {
        $time = $this->binaryDataReader->readUInt24();
        if (0 == $time)
        {
            return null;
        }

        $year = ($time & ((1 << 15) - 1) << 9) >> 9;
        $month = ($time & ((1 << 4) - 1) << 5) >> 5;
        $day = ($time & ((1 << 5) - 1));
        if ($year == 0 || $month == 0 || $day == 0)
        {
            return null;
        }

        return (new \DateTime())->setDate($year, $month, $day)->format('Y-m-d');
    }

    /**
     * @param array $column
     * @return array
     * @throws BinLogException
     */
    private function getSet(array $column)
    {
        // we read set columns as a bitmap telling us which options are enabled
        $bit_mask = $this->binaryDataReader->readUIntBySize($column['size']);
        $sets = [];
        foreach ($column['set_values'] as $k => $item)
        {
            if ($bit_mask & pow(2, $k))
            {
                $sets[] = $item;
            }
        }

        return $sets;
    }

    /**
     * Read MySQL BIT type
     * @param array $column
     * @return string
     */
    private function getBit(array $column)
    {
        $res = '';
        for ($byte = 0; $byte < $column['bytes']; $byte++)
        {
            $current_byte = '';
            $data = $this->binaryDataReader->readUInt8();
            if (0 === $byte)
            {
                if (1 === $column['bytes'])
                {
                    $end = $column['bits'];
                }
                else
                {
                    $end = $column['bits'] % 8;
                    if (0 === $end)
                    {
                        $end = 8;
                    }
                }
            }
            else
            {
                $end = 8;
            }

            for ($bit = 0; $bit < $end; $bit++)
            {
                if ($data & (1 << $bit))
                {
                    $current_byte .= '1';
                }
                else
                {
                    $current_byte .= '0';
                }

            }
            $res .= strrev($current_byte);
        }

        return $res;
    }

    /**
     * @return DeleteRowsDTO
     */
    public function makeDeleteRowsDTO()
    {
        $this->rowInit();

        if (false === $this->process)
        {
            return null;
        }

        $values = $this->getValues();

        return new DeleteRowsDTO(
            $this->eventInfo,
            $this->tableMapDatabase,
            $this->tableMapTableName,
            $this->tableMapColumnsAmount,
            count($values),
            $values
        );
    }

    /**
     * @return UpdateRowsDTO
     */
    public function makeUpdateRowsDTO()
    {
        $this->rowInit();

        if (false === $this->process)
        {
            return null;
        }

        $columnsBinarySize = $this->getColumnsBinarySize($this->tableMapColumnsAmount);
        $beforeBinaryData = $this->binaryDataReader->read($columnsBinarySize);
        $afterBinaryData = $this->binaryDataReader->read($columnsBinarySize);

        $values = [];
        while (false === $this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader()))
        {
            $values[] = [
                'before' => $this->getColumnData($beforeBinaryData),
                'after' => $this->getColumnData($afterBinaryData)
            ];
        }

        return new UpdateRowsDTO(
            $this->eventInfo,
            $this->tableMapDatabase,
            $this->tableMapTableName,
            $this->tableMapColumnsAmount,
            count($values),
            $values
        );
    }

    /**
     * @return array
     * @throws BinLogException
     */
    private function getValues()
    {
        $columnsBinarySize = $this->getColumnsBinarySize($this->tableMapColumnsAmount);
        $binaryData = $this->binaryDataReader->read($columnsBinarySize);

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader()))
        {
            $values[] = $this->getColumnData($binaryData);
        }

        return $values;
    }
}
