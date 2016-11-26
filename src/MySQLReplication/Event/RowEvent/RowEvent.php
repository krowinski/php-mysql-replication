<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\Exception\ConfigException;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventCommon;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\Exception\EventException;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\RepositoryInterface;

/**
 * Class RowEvent
 * @package MySQLReplication\RowEvent
 */
class RowEvent extends EventCommon
{
    /**
     * @var TableMap[]
     */
    private static $tableMapCache;
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
     * @var RepositoryInterface
     */
    private $repository;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TableMap
     */
    private $currentTableMap;
    /**
     * @var JsonBinaryDecoderFactory
     */
    private $jsonBinaryDecoderFactory;

    /**
     * RowEvent constructor.
     * @param Config $config
     * @param RepositoryInterface $repository
     * @param BinaryDataReader $binaryDataReader
     * @param EventInfo $eventInfo
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     */
    public function __construct(
        Config $config,
        RepositoryInterface $repository,
        BinaryDataReader $binaryDataReader,
        EventInfo $eventInfo,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
    )
    {
        parent::__construct($eventInfo, $binaryDataReader);

        $this->repository = $repository;
        $this->config = $config;
        $this->jsonBinaryDecoderFactory = $jsonBinaryDecoderFactory;
    }

    /**
     * This evenement describe the structure of a table.
     * It's send before a change append on a table.
     * A end user of the lib should have no usage of this
     *
     * @return TableMapDTO
     * @throws BinaryDataReaderException
     * @throws ConfigException
     */
    public function makeTableMapDTO()
    {
        $data = [];
        $data['table_id'] = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);
        $data['schema_length'] = $this->binaryDataReader->readUInt8();
        $data['schema_name'] = $this->binaryDataReader->read($data['schema_length']);

        if ([] !== $this->config->getDatabasesOnly() && !in_array($data['schema_name'], $this->config->getDatabasesOnly(), true))
        {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['table_length'] = $this->binaryDataReader->readUInt8();
        $data['table_name'] = $this->binaryDataReader->read($data['table_length']);

        if ([] !== $this->config->getTablesOnly() && !in_array($data['table_name'], $this->config->getTablesOnly(), true))
        {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['columns_amount'] = $this->binaryDataReader->readCodedBinary();
        $data['column_types'] = $this->binaryDataReader->read($data['columns_amount']);

        // automatically clear table cache to save memory
        if (count(self::$tableMapCache) > $this->config->getTableCacheSize())
        {
            self::$tableMapCache = array_slice(self::$tableMapCache, ceil($this->config->getTableCacheSize() / 2), null, true);
        }

        // already in cache don't parse
        if (isset(self::$tableMapCache[$data['table_id']]))
        {
            return new TableMapDTO(
                $this->eventInfo,
                self::$tableMapCache[$data['table_id']]
            );
        }

        $this->binaryDataReader->readCodedBinary();

        $columns = $this->repository->getFields($data['schema_name'], $data['table_name']);

        $fields = [];
        // if you drop tables and parse of logs you will get empty scheme
        if (!empty($columns))
        {
            $columnLength = strlen($data['column_types']);
            for ($i = 0; $i < $columnLength; $i++)
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
                        'REFERENCED_TABLE_NAME' => '',
                        'REFERENCED_COLUMN_NAME' => '',
                    ];

                    $type = ConstFieldType::IGNORE;
                }
                else
                {
                    $type = ord($data['column_types'][$i]);
                }

                $fields[$i] = Columns::parse($type, $columns[$i], $this->binaryDataReader);
            }
        }

        // save to cache
        self::$tableMapCache[$data['table_id']] = new TableMap(
            $data['schema_name'],
            $data['table_name'],
            $data['table_id'],
            $data['columns_amount'],
            $fields
        );

        return new TableMapDTO(
            $this->eventInfo,
            self::$tableMapCache[$data['table_id']]
        );
    }

    /**
     * @return WriteRowsDTO
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeWriteRowsDTO()
    {
        if (false === $this->rowInit())
        {
            return null;
        }

        $values = $this->getValues();

        return new WriteRowsDTO(
            $this->eventInfo,
            $this->currentTableMap,
            count($values),
            $values
        );
    }

    /**
     * @return bool
     * @throws BinaryDataReaderException
     */
    protected function rowInit()
    {
        $tableId = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);

        if (in_array($this->eventInfo->getType(), [
            ConstEventType::DELETE_ROWS_EVENT_V2,
            ConstEventType::WRITE_ROWS_EVENT_V2,
            ConstEventType::UPDATE_ROWS_EVENT_V2
        ], true))
        {
            $this->binaryDataReader->read($this->binaryDataReader->readUInt16() / 8);
        }

        $this->binaryDataReader->readCodedBinary();

        if (isset(self::$tableMapCache[$tableId]) && [] !== self::$tableMapCache[$tableId]->getFields())
        {
            $this->currentTableMap = self::$tableMapCache[$tableId];

            return true;
        }
        unset(self::$tableMapCache[$tableId]);

        return false;
    }

    /**
     * @return array
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    protected function getValues()
    {
        $columnsBinarySize = $this->getColumnsBinarySize($this->currentTableMap->getColumnsAmount());
        $binaryData = $this->binaryDataReader->read($columnsBinarySize);

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader()))
        {
            $values[] = $this->getColumnData($binaryData);
        }

        return $values;
    }

    /**
     * @param int $columnsAmount
     * @return int
     */
    protected function getColumnsBinarySize($columnsAmount)
    {
        return (int)(($columnsAmount + 7) / 8);
    }

    /**
     * @param int $colsBitmap
     * @return array
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    protected function getColumnData($colsBitmap)
    {
        $values = [];

        // null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        // see http://dev.mysql.com/doc/internals/en/rows-event.html
        $null_bitmap = $this->binaryDataReader->read($this->getColumnsBinarySize($this->bitCount($colsBitmap)));
        $nullBitmapIndex = 0;

        foreach ($this->currentTableMap->getFields() as $i => $column)
        {
            $name = $column['name'];

            if ($this->bitGet($colsBitmap, $i) === 0)
            {
                $values[$name] = null;
                continue;
            }

            if ($this->checkNull($null_bitmap, $nullBitmapIndex))
            {
                $values[$name] = null;
            }
            elseif ($column['type'] === ConstFieldType::IGNORE)
            {
                $values[$name] = null;
            }
            elseif ($column['type'] === ConstFieldType::TINY)
            {
                if (true === $column['unsigned'])
                {
                    $values[$name] = $this->binaryDataReader->readUInt8();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt8();
                }
            }
            elseif ($column['type'] === ConstFieldType::SHORT)
            {
                if (true === $column['unsigned'])
                {
                    $values[$name] = $this->binaryDataReader->readUInt16();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt16();
                }
            }
            elseif ($column['type'] === ConstFieldType::LONG)
            {
                if (true === $column['unsigned'])
                {
                    $values[$name] = $this->binaryDataReader->readUInt32();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt32();
                }
            }
            elseif ($column['type'] === ConstFieldType::LONGLONG)
            {
                if (true === $column['unsigned'])
                {
                    $values[$name] = $this->binaryDataReader->readUInt64();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt64();
                }
            }
            elseif ($column['type'] === ConstFieldType::INT24)
            {
                if (true === $column['unsigned'])
                {
                    $values[$name] = $this->binaryDataReader->readUInt24();
                }
                else
                {
                    $values[$name] = $this->binaryDataReader->readInt24();
                }
            }
            elseif ($column['type'] === ConstFieldType::FLOAT)
            {
                // http://dev.mysql.com/doc/refman/5.7/en/floating-point-types.html FLOAT(7,4)
                $values[$name] = round($this->binaryDataReader->readFloat(), 4);
            }
            elseif ($column['type'] === ConstFieldType::DOUBLE)
            {
                $values[$name] = $this->binaryDataReader->readDouble();
            }
            elseif ($column['type'] === ConstFieldType::VARCHAR || $column['type'] === ConstFieldType::STRING)
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
            elseif ($column['type'] === ConstFieldType::NEWDECIMAL)
            {
                $values[$name] = $this->getDecimal($column);
            }
            elseif ($column['type'] === ConstFieldType::BLOB)
            {
                $values[$name] = $this->getString($column['length_size'], $column);
            }
            elseif ($column['type'] === ConstFieldType::DATETIME)
            {
                $values[$name] = $this->getDatetime();
            }
            elseif ($column['type'] === ConstFieldType::DATETIME2)
            {
                $values[$name] = $this->getDatetime2($column);
            }
            elseif ($column['type'] === ConstFieldType::TIMESTAMP)
			{
				$values[$name] = date('c', $this->binaryDataReader->readUInt32());
			}
            elseif ($column['type'] === ConstFieldType::TIME2)
            {
                $values[$name] = $this->getTime2($column);
            }
            elseif ($column['type'] === ConstFieldType::TIMESTAMP2)
            {
                $values[$name] = $this->getTimestamp2($column);
            }
            elseif ($column['type'] === ConstFieldType::DATE)
            {
                $values[$name] = $this->getDate();
            }
            elseif ($column['type'] === ConstFieldType::YEAR)
            {
                $values[$name] = $this->binaryDataReader->readUInt8() + 1900;
            }
            elseif ($column['type'] === ConstFieldType::ENUM)
            {
                $value = $this->binaryDataReader->readUIntBySize($column['size']) - 1;

                $values[$name] = '';
                // check if given value exists in enums, if there not existing enum mysql sets to empty string.
                if (array_key_exists($value, $column['enum_values']))
                {
                    $values[$name] = $column['enum_values'][$value];
                }
            }
            elseif ($column['type'] === ConstFieldType::SET)
            {
                $values[$name] = $this->getSet($column);
            }
            elseif ($column['type'] === ConstFieldType::BIT)
            {
                $values[$name] = $this->getBit($column);
            }
            elseif ($column['type'] === ConstFieldType::GEOMETRY)
            {
                $values[$name] = $this->binaryDataReader->readLengthCodedPascalString($column['length_size']);
            }
            elseif ($column['type'] === ConstFieldType::JSON)
            {
                $values[$name] = $this->jsonBinaryDecoderFactory->makeJsonBinaryDecoder(
                    $this->binaryDataReader->readLengthCodedPascalString($column['length_size'])
                )->parseToString();
            }
            else
            {
                throw new MySQLReplicationException('Unknown row type: ' . $column['type']);
            }

            ++$nullBitmapIndex;
        }

        return $values;
    }

    /**
     * @param string $bitmap
     * @return int
     */
    protected function bitCount($bitmap)
    {
        $n = 0;
        $bitmapLength = strlen($bitmap);
        for ($i = 0; $i < $bitmapLength; $i++)
        {
            $bit = $bitmap[$i];
            if (true === is_string($bit))
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
    protected function bitGet($bitmap, $position)
    {
        $bit = $bitmap[(int)($position / 8)];
        if (true === is_string($bit))
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
    protected function checkNull($nullBitmap, $position)
    {
        $bit = $nullBitmap[(int)($position / 8)];
        if (true === is_string($bit))
        {
            $bit = ord($bit);
        }

        return $bit & (1 << ($position % 8));
    }

    /**
     * @param int $size
     * @param array $column
     * @return string
     * @throws BinaryDataReaderException
     */
    protected function getString($size, array $column)
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
     * @throws BinaryDataReaderException
     */
    protected function getDecimal(array $column)
    {
        $digits_per_integer = 9;
        $compressed_bytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
        $integral = $column['precision'] - $column['decimals'];
        $uncomp_integral = (int)($integral / $digits_per_integer);
        $uncomp_fractional = (int)($column['decimals'] / $digits_per_integer);
        $comp_integral = $integral - ($uncomp_integral * $digits_per_integer);
        $comp_fractional = $column['decimals'] - ($uncomp_fractional * $digits_per_integer);

        $value = $this->binaryDataReader->readUInt8();
        if (0 !== ($value & 0x80))
        {
            $mask = 0;
            $res = '';
        }
        else
        {
            $mask = -1;
            $res = '-';
        }
        $this->binaryDataReader->unread(pack('C', $value ^ 0x80));

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
    protected function getDatetime()
    {
        $value = $this->binaryDataReader->readUInt64();
        // nasty mysql 0000-00-00 dates
        if ('0' === $value)
        {
            return null;
        }

        $date = $value / 1000000;
        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year === 0 || $month === 0 || $day === 0)
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
     * @throws BinaryDataReaderException
     */
    protected function getDatetime2(array $column)
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
        if (array_sum(\DateTime::getLastErrors()) > 0)
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
    protected function getBinarySlice($binary, $start, $size, $data_length)
    {
        $binary >>= $data_length - ($start + $size);
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
     * @throws BinaryDataReaderException
     */
    protected function getFSP(array $column)
    {
        $read = 0;
        $time = '';
        if ($column['fsp'] === 1 || $column['fsp'] === 2)
        {
            $read = 1;
        }
        elseif ($column['fsp'] === 3 || $column['fsp'] === 4)
        {
            $read = 2;
        }
        elseif ($column ['fsp'] === 5 || $column['fsp'] === 6)
        {
            $read = 3;
        }
        if ($read > 0)
        {
            $microsecond = $this->binaryDataReader->readIntBeBySize($read);

            $time = $microsecond;
            if ($column['fsp'] % 2)
            {
                $time = (int)($microsecond / 10);
            }
        }

        return $time;
    }

    /**
     * TIME encoding for non fractional part:
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
     * @throws BinaryDataReaderException
     */
    protected function getTime2(array $column)
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
     * @throws EventException
     * @throws BinaryDataReaderException
     */
    protected function getTimestamp2(array $column)
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
    protected function getDate()
    {
        $time = $this->binaryDataReader->readUInt24();
        if (0 === $time)
        {
            return null;
        }

        $year = ($time & ((1 << 15) - 1) << 9) >> 9;
        $month = ($time & ((1 << 4) - 1) << 5) >> 5;
        $day = ($time & ((1 << 5) - 1));
        if ($year === 0 || $month === 0 || $day === 0)
        {
            return null;
        }

        return (new \DateTime())->setDate($year, $month, $day)->format('Y-m-d');
    }

    /**
     * @param array $column
     * @return array
     * @throws EventException
     * @throws BinaryDataReaderException
     */
    protected function getSet(array $column)
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
    protected function getBit(array $column)
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
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeDeleteRowsDTO()
    {
        if (false === $this->rowInit())
        {
            return null;
        }

        $values = $this->getValues();

        return new DeleteRowsDTO(
            $this->eventInfo,
            $this->currentTableMap,
            count($values),
            $values
        );
    }

    /**
     * @return UpdateRowsDTO
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeUpdateRowsDTO()
    {
        if (false === $this->rowInit())
        {
            return null;
        }

        $columnsBinarySize = $this->getColumnsBinarySize($this->currentTableMap->getColumnsAmount());
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
            $this->currentTableMap,
            count($values),
            $values
        );
    }
}
