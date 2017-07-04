<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventCommon;
use MySQLReplication\Event\EventException;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class RowEvent
 * @package MySQLReplication\RowEvent
 */
class RowEvent extends EventCommon
{
    /**
     * @var array
     */
    private static $bitCountInByte = [
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
     * @var CacheInterface
     */
    private $cache;

    /**
     * RowEvent constructor.
     * @param Config $config
     * @param RepositoryInterface $repository
     * @param BinaryDataReader $binaryDataReader
     * @param EventInfo $eventInfo
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        Config $config,
        RepositoryInterface $repository,
        BinaryDataReader $binaryDataReader,
        EventInfo $eventInfo,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory,
        CacheInterface $cache
    ) {
        parent::__construct($eventInfo, $binaryDataReader);

        $this->repository = $repository;
        $this->config = $config;
        $this->jsonBinaryDecoderFactory = $jsonBinaryDecoderFactory;
        $this->cache = $cache;
    }

    /**
     * This describe the structure of a table.
     * It's send before a change append on a table.
     * A end user of the lib should have no usage of this
     *
     * @return TableMapDTO
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    public function makeTableMapDTO()
    {
        $data = [];
        $data['table_id'] = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);
        $data['schema_length'] = $this->binaryDataReader->readUInt8();
        $data['schema_name'] = $this->binaryDataReader->read($data['schema_length']);

        if ([] !== $this->config->getDatabasesOnly() && !in_array(
                $data['schema_name'], $this->config->getDatabasesOnly(), true
            )
        ) {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['table_length'] = $this->binaryDataReader->readUInt8();
        $data['table_name'] = $this->binaryDataReader->read($data['table_length']);

        if ([] !== $this->config->getTablesOnly() && !in_array(
                $data['table_name'], $this->config->getTablesOnly(), true
            )
        ) {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['columns_amount'] = $this->binaryDataReader->readCodedBinary();
        $data['column_types'] = $this->binaryDataReader->read($data['columns_amount']);

        if ($this->cache->has($data['table_id'])) {
            return new TableMapDTO($this->eventInfo, $this->cache->get($data['table_id']));
        }

        $this->binaryDataReader->readCodedBinary();

        $columns = $this->repository->getFields($data['schema_name'], $data['table_name']);

        $fields = [];
        // if you drop tables and parse of logs you will get empty scheme
        if (!empty($columns)) {
            $columnLength = strlen($data['column_types']);
            for ($i = 0; $i < $columnLength; ++$i) {
                // this a dirty hack to prevent row events containing columns which have been dropped
                if (!isset($columns[$i])) {
                    $columns[$i] = [
                        'COLUMN_NAME' => 'DROPPED_COLUMN_' . $i,
                        'COLLATION_NAME' => null,
                        'CHARACTER_SET_NAME' => null,
                        'COLUMN_COMMENT' => null,
                        'COLUMN_TYPE' => 'BLOB',
                        'COLUMN_KEY' => ''
                    ];

                    $type = ConstFieldType::IGNORE;
                } else {
                    $type = ord($data['column_types'][$i]);
                }

                $fields[$i] = Columns::parse($type, $columns[$i], $this->binaryDataReader);
            }
        }

        $tableMap = new TableMap(
            $data['schema_name'],
            $data['table_name'],
            $data['table_id'],
            $data['columns_amount'],
            $fields
        );

        $this->cache->set($data['table_id'], $tableMap);

        return new TableMapDTO($this->eventInfo, $tableMap);
    }

    /**
     * @return WriteRowsDTO|null
     * @throws InvalidArgumentException
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeWriteRowsDTO()
    {
        if (!$this->rowInit()) {
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
     * @throws InvalidArgumentException
     * @throws BinaryDataReaderException
     */
    protected function rowInit()
    {
        $tableId = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);

        if (in_array(
            $this->eventInfo->getType(), [
            ConstEventType::DELETE_ROWS_EVENT_V2,
            ConstEventType::WRITE_ROWS_EVENT_V2,
            ConstEventType::UPDATE_ROWS_EVENT_V2
        ], true
        )) {
            $this->binaryDataReader->read($this->binaryDataReader->readUInt16() / 8);
        }

        $this->binaryDataReader->readCodedBinary();

        if ($this->cache->has($tableId)) {
            /** @var TableMap $tableMap */
            $tableMap = $this->cache->get($tableId);
            if ([] !== $tableMap->getFields()) {
                $this->currentTableMap = $tableMap;

                return true;
            }
            $this->cache->delete($tableId);
        }

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
        $binaryData = $this->binaryDataReader->read(
            $this->getColumnsBinarySize($this->currentTableMap->getColumnsAmount())
        );

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader())) {
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

        foreach ($this->currentTableMap->getFields() as $i => $column) {
            $name = $column['name'];

            if (0 === $this->bitGet($colsBitmap, $i)) {
                $values[$name] = null;
                continue;
            }

            if ($this->checkNull($null_bitmap, $nullBitmapIndex)) {
                $values[$name] = null;
            } elseif ($column['type'] === ConstFieldType::IGNORE) {
                $values[$name] = null;
            } elseif ($column['type'] === ConstFieldType::TINY) {
                if ($column['unsigned']) {
                    $values[$name] = $this->binaryDataReader->readUInt8();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt8();
                }
            } elseif ($column['type'] === ConstFieldType::SHORT) {
                if ($column['unsigned']) {
                    $values[$name] = $this->binaryDataReader->readUInt16();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt16();
                }
            } elseif ($column['type'] === ConstFieldType::LONG) {
                if ($column['unsigned']) {
                    $values[$name] = $this->binaryDataReader->readUInt32();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt32();
                }
            } elseif ($column['type'] === ConstFieldType::LONGLONG) {
                if ($column['unsigned']) {
                    $values[$name] = $this->binaryDataReader->readUInt64();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt64();
                }
            } elseif ($column['type'] === ConstFieldType::INT24) {
                if ($column['unsigned']) {
                    $values[$name] = $this->binaryDataReader->readUInt24();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt24();
                }
            } elseif ($column['type'] === ConstFieldType::FLOAT) {
                // http://dev.mysql.com/doc/refman/5.7/en/floating-point-types.html FLOAT(7,4)
                $values[$name] = round($this->binaryDataReader->readFloat(), 4);
            } elseif ($column['type'] === ConstFieldType::DOUBLE) {
                $values[$name] = $this->binaryDataReader->readDouble();
            } elseif ($column['type'] === ConstFieldType::VARCHAR || $column['type'] === ConstFieldType::STRING) {
                $values[$name] = $column['max_length'] > 255 ? $this->getString(2) : $this->getString(1);
            } elseif ($column['type'] === ConstFieldType::NEWDECIMAL) {
                $values[$name] = $this->getDecimal($column);
            } elseif ($column['type'] === ConstFieldType::BLOB) {
                $values[$name] = $this->getString($column['length_size']);
            } elseif ($column['type'] === ConstFieldType::DATETIME) {
                $values[$name] = $this->getDatetime();
            } elseif ($column['type'] === ConstFieldType::DATETIME2) {
                $values[$name] = $this->getDatetime2($column);
            } elseif ($column['type'] === ConstFieldType::TIMESTAMP) {
                $values[$name] = date('c', $this->binaryDataReader->readUInt32());
            } elseif ($column['type'] === ConstFieldType::TIME2) {
                $values[$name] = $this->getTime2($column);
            } elseif ($column['type'] === ConstFieldType::TIMESTAMP2) {
                $values[$name] = $this->getTimestamp2($column);
            } elseif ($column['type'] === ConstFieldType::DATE) {
                $values[$name] = $this->getDate();
            } elseif ($column['type'] === ConstFieldType::YEAR) {
                // https://dev.mysql.com/doc/refman/5.7/en/year.html
                $year = $this->binaryDataReader->readUInt8();
                $values[$name] = 0 === $year ? null : 1900 + $year;
            } elseif ($column['type'] === ConstFieldType::ENUM) {
                $values[$name] = $this->getEnum($column);
            } elseif ($column['type'] === ConstFieldType::SET) {
                $values[$name] = $this->getSet($column);
            } elseif ($column['type'] === ConstFieldType::BIT) {
                $values[$name] = $this->getBit($column);
            } elseif ($column['type'] === ConstFieldType::GEOMETRY) {
                $values[$name] = $this->getString($column['length_size']);
            } elseif ($column['type'] === ConstFieldType::JSON) {
                $values[$name] = $this->jsonBinaryDecoderFactory->makeJsonBinaryDecoder(
                    $this->getString($column['length_size'])
                )->parseToString();
            } else {
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
        for ($i = 0; $i < $bitmapLength; ++$i) {
            $bit = $bitmap[$i];
            if (is_string($bit)) {
                $bit = ord($bit);
            }
            $n += self::$bitCountInByte[$bit];
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
        if (is_string($bit)) {
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
        if (is_string($bit)) {
            $bit = ord($bit);
        }

        return $bit & (1 << ($position % 8));
    }

    /**
     * @param int $size
     * @return string
     * @throws BinaryDataReaderException
     */
    protected function getString($size)
    {
        return $this->binaryDataReader->readLengthCodedPascalString($size);
    }

    /**
     * Read MySQL's new decimal format introduced in MySQL 5
     * https://dev.mysql.com/doc/refman/5.6/en/precision-math-decimal-characteristics.html
     * @param array $column
     * @return string
     * @throws BinaryDataReaderException
     */
    protected function getDecimal(array $column)
    {
        $digitsPerInteger = 9;
        $compressedBytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
        $integral = $column['precision'] - $column['decimals'];
        $unCompIntegral = (int)($integral / $digitsPerInteger);
        $unCompFractional = (int)($column['decimals'] / $digitsPerInteger);
        $compIntegral = $integral - ($unCompIntegral * $digitsPerInteger);
        $compFractional = $column['decimals'] - ($unCompFractional * $digitsPerInteger);

        $value = $this->binaryDataReader->readUInt8();
        if (0 !== ($value & 0x80)) {
            $mask = 0;
            $res = '';
        } else {
            $mask = -1;
            $res = '-';
        }
        $this->binaryDataReader->unread(pack('C', $value ^ 0x80));

        $size = $compressedBytes[$compIntegral];
        if ($size > 0) {
            $value = $this->binaryDataReader->readIntBeBySize($size) ^ $mask;
            $res .= $value;
        }

        for ($i = 0; $i < $unCompIntegral; ++$i) {
            $value = $this->binaryDataReader->readInt32Be() ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $res .= '.';

        for ($i = 0; $i < $unCompFractional; ++$i) {
            $value = $this->binaryDataReader->readInt32Be() ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $size = $compressedBytes[$compFractional];
        if ($size > 0) {
            $value = $this->binaryDataReader->readIntBeBySize($size) ^ $mask;
            $res .= sprintf('%0' . $compFractional . 'd', $value);
        }

        return bcmul($res, 1, $column['precision']);
    }

    /**
     * @return null|string
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    protected function getDatetime()
    {
        $value = $this->binaryDataReader->readUInt64();
        // nasty mysql 0000-00-00 dates
        if ('0' === $value) {
            return null;
        }

        $date = $value / 1000000;
        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year === 0 || $month === 0 || $day === 0) {
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
     * @return string|null
     * @throws BinaryDataReaderException
     * @link https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
     */
    protected function getDatetime2(array $column)
    {
        $data = $this->binaryDataReader->readIntBeBySize(5);

        $yearMonth = $this->binaryDataReader->getBinarySlice($data, 1, 17, 40);

        $year = (int)($yearMonth / 13);
        $month = $yearMonth % 13;
        $day = $this->binaryDataReader->getBinarySlice($data, 18, 5, 40);
        $hour = $this->binaryDataReader->getBinarySlice($data, 23, 5, 40);
        $minute = $this->binaryDataReader->getBinarySlice($data, 28, 6, 40);
        $second = $this->binaryDataReader->getBinarySlice($data, 34, 6, 40);

        try {
            $date = new \DateTime($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);
        } catch (\Exception $exception) {
            return null;
        }
        // not all errors are thrown as exception :(
        if (array_sum(\DateTime::getLastErrors()) > 0) {
            return null;
        }

        return $date->format('Y-m-d H:i:s') . $this->getFSP($column);
    }

    /**
     * @param array $column
     * @return string
     * @throws BinaryDataReaderException
     * @link https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
     */
    protected function getFSP(array $column)
    {
        $read = 0;
        $time = '';
        if ($column['fsp'] === 1 || $column['fsp'] === 2) {
            $read = 1;
        } elseif ($column['fsp'] === 3 || $column['fsp'] === 4) {
            $read = 2;
        } elseif ($column ['fsp'] === 5 || $column['fsp'] === 6) {
            $read = 3;
        }
        if ($read > 0) {
            $microsecond = $this->binaryDataReader->readIntBeBySize($read);

            $time = $microsecond;
            if ($column['fsp'] % 2) {
                $time = (int)($microsecond / 10);
            }
        }

        return (string)$time;
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

        $hour = $this->binaryDataReader->getBinarySlice($data, 2, 10, 24);
        $minute = $this->binaryDataReader->getBinarySlice($data, 12, 6, 24);
        $second = $this->binaryDataReader->getBinarySlice($data, 18, 6, 24);

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
        $datetime = date('Y-m-d H:i:s', $this->binaryDataReader->readInt32Be());
        $fsp = $this->getFSP($column);
        if ('' !== $fsp) {
            $datetime .= '.' . $fsp;
        }

        return $datetime;
    }

    /**
     * @return string
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    protected function getDate()
    {
        $time = $this->binaryDataReader->readUInt24();
        if (0 === $time) {
            return null;
        }

        $year = ($time & ((1 << 15) - 1) << 9) >> 9;
        $month = ($time & ((1 << 4) - 1) << 5) >> 5;
        $day = ($time & ((1 << 5) - 1));
        if ($year === 0 || $month === 0 || $day === 0) {
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
        foreach ((array)$column['set_values'] as $k => $item) {
            if ($bit_mask & pow(2, $k)) {
                $sets[] = $item;
            }
        }

        return $sets;
    }

    /**
     * Read MySQL BIT type
     * @param array $column
     * @return string
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    protected function getBit(array $column)
    {
        $res = '';
        for ($byte = 0; $byte < $column['bytes']; ++$byte) {
            $current_byte = '';
            $data = $this->binaryDataReader->readUInt8();
            if (0 === $byte) {
                if (1 === $column['bytes']) {
                    $end = $column['bits'];
                } else {
                    $end = $column['bits'] % 8;
                    if (0 === $end) {
                        $end = 8;
                    }
                }
            } else {
                $end = 8;
            }

            for ($bit = 0; $bit < $end; ++$bit) {
                if ($data & (1 << $bit)) {
                    $current_byte .= '1';
                } else {
                    $current_byte .= '0';
                }

            }
            $res .= strrev($current_byte);
        }

        return $res;
    }

    /**
     * @return DeleteRowsDTO
     * @throws InvalidArgumentException
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeDeleteRowsDTO()
    {
        if (!$this->rowInit()) {
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
     * @throws InvalidArgumentException
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws MySQLReplicationException
     */
    public function makeUpdateRowsDTO()
    {
        if (!$this->rowInit()) {
            return null;
        }

        $columnsBinarySize = $this->getColumnsBinarySize($this->currentTableMap->getColumnsAmount());
        $beforeBinaryData = $this->binaryDataReader->read($columnsBinarySize);
        $afterBinaryData = $this->binaryDataReader->read($columnsBinarySize);

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader())) {
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

    /**
     * @param array $column
     * @return string
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    protected function getEnum(array $column)
    {
        $value = $this->binaryDataReader->readUIntBySize($column['size']) - 1;

        // check if given value exists in enums, if there not existing enum mysql returns empty string.
        if (array_key_exists($value, $column['enum_values'])) {
            return $column['enum_values'][$value];
        }

        return '';
    }
}
