<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use DateTime;
use Exception;
use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\EventCommon;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderService;
use MySQLReplication\Repository\FieldDTO;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class RowEvent extends EventCommon
{
    private static array $bitCountInByte = [
        0,
        1,
        1,
        2,
        1,
        2,
        2,
        3,
        1,
        2,
        2,
        3,
        2,
        3,
        3,
        4,
        1,
        2,
        2,
        3,
        2,
        3,
        3,
        4,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        1,
        2,
        2,
        3,
        2,
        3,
        3,
        4,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        1,
        2,
        2,
        3,
        2,
        3,
        3,
        4,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        4,
        5,
        5,
        6,
        5,
        6,
        6,
        7,
        1,
        2,
        2,
        3,
        2,
        3,
        3,
        4,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        4,
        5,
        5,
        6,
        5,
        6,
        6,
        7,
        2,
        3,
        3,
        4,
        3,
        4,
        4,
        5,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        4,
        5,
        5,
        6,
        5,
        6,
        6,
        7,
        3,
        4,
        4,
        5,
        4,
        5,
        5,
        6,
        4,
        5,
        5,
        6,
        5,
        6,
        6,
        7,
        4,
        5,
        5,
        6,
        5,
        6,
        6,
        7,
        5,
        6,
        6,
        7,
        6,
        7,
        7,
        8,
    ];

    private ?TableMap $currentTableMap;

    public function __construct(
        private readonly RepositoryInterface $repository,
        protected BinaryDataReader $binaryDataReader,
        protected EventInfo $eventInfo,
        private readonly TableMapCache $tableMapCache,
        private readonly Config $config,
        protected BinLogServerInfo $binLogServerInfo,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($eventInfo, $binaryDataReader, $binLogServerInfo);
    }

    /**
     * This describes the structure of a table.
     * It's send before a change append on a table.
     * An end user of the lib should have no usage of this
     */
    public function makeTableMapDTO(): ?TableMapDTO
    {
        $data = [];
        $data['table_id'] = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);
        $data['schema_length'] = $this->binaryDataReader->readUInt8();
        $data['schema_name'] = $this->binaryDataReader->read($data['schema_length']);

        if ($this->config->checkDataBasesOnly($data['schema_name'])) {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['table_length'] = $this->binaryDataReader->readUInt8();
        $data['table_name'] = $this->binaryDataReader->read($data['table_length']);

        if ($this->config->checkTablesOnly($data['table_name'])) {
            return null;
        }

        $this->binaryDataReader->advance(1);
        $data['columns_amount'] = (int)$this->binaryDataReader->readCodedBinary();
        $data['column_types'] = $this->binaryDataReader->read($data['columns_amount']);

        if ($this->tableMapCache->has($data['table_id'])) {
            return new TableMapDTO($this->eventInfo, $this->tableMapCache->get($data['table_id']));
        }

        $this->binaryDataReader->readCodedBinary();

        $fieldDTOCollection = $this->repository->getFields($data['schema_name'], $data['table_name']);
        $columnDTOCollection = new ColumnDTOCollection();
        // if you drop tables and parse of logs you will get empty scheme
        if (!$fieldDTOCollection->isEmpty()) {
            $columnLength = strlen($data['column_types']);
            for ($offset = 0; $offset < $columnLength; ++$offset) {
                // this a dirty hack to prevent row events containing columns which have been dropped
                if ($fieldDTOCollection->offsetExists($offset)) {
                    $type = ord($data['column_types'][$offset]);
                } else {
                    $fieldDTOCollection->offsetSet($offset, FieldDTO::makeDummy($offset));
                    $type = ConstFieldType::IGNORE;
                }

                $fieldDTO = $fieldDTOCollection->offsetGet($offset);
                if ($fieldDTO) {
                    $columnDTOCollection->set($offset, ColumnDTO::make($type, $fieldDTO, $this->binaryDataReader));
                }
            }
        }

        $tableMap = new TableMap(
            $data['schema_name'],
            $data['table_name'],
            $data['table_id'],
            $data['columns_amount'],
            $columnDTOCollection
        );

        $this->tableMapCache->set($data['table_id'], $tableMap);

        return new TableMapDTO($this->eventInfo, $tableMap);
    }

    public function makeWriteRowsDTO(): ?WriteRowsDTO
    {
        $this->currentTableMap = $this->findTableMap();
        if ($this->currentTableMap === null) {
            return null;
        }

        $values = $this->getValues();

        return new WriteRowsDTO($this->eventInfo, $this->currentTableMap, count($values), $values);
    }

    public function makeDeleteRowsDTO(): ?DeleteRowsDTO
    {
        $this->currentTableMap = $this->findTableMap();
        if ($this->currentTableMap === null) {
            return null;
        }

        $values = $this->getValues();

        return new DeleteRowsDTO($this->eventInfo, $this->currentTableMap, count($values), $values);
    }

    public function makeUpdateRowsDTO(): ?UpdateRowsDTO
    {
        $this->currentTableMap = $this->findTableMap();
        if ($this->currentTableMap === null) {
            return null;
        }

        $columnsBinarySize = $this->getColumnsBinarySize($this->currentTableMap->columnsAmount);
        $beforeBinaryData = $this->binaryDataReader->read($columnsBinarySize);
        $afterBinaryData = $this->binaryDataReader->read($columnsBinarySize);

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader())) {
            $values[] = [
                'before' => $this->getColumnData($beforeBinaryData),
                'after' => $this->getColumnData($afterBinaryData),
            ];
        }

        return new UpdateRowsDTO($this->eventInfo, $this->currentTableMap, count($values), $values);
    }

    protected function findTableMap(): ?TableMap
    {
        $tableId = $this->binaryDataReader->readTableId();
        $this->binaryDataReader->advance(2);

        if (in_array(
            $this->eventInfo->type,
            [
                ConstEventType::DELETE_ROWS_EVENT_V2->value,
                ConstEventType::WRITE_ROWS_EVENT_V2->value,
                ConstEventType::UPDATE_ROWS_EVENT_V2->value,
            ],
            true
        )) {
            $this->binaryDataReader->read((int)($this->binaryDataReader->readUInt16() / 8));
        }

        $this->binaryDataReader->readCodedBinary();

        if ($this->tableMapCache->has($tableId)) {
            return $this->tableMapCache->get($tableId);
        }

        $this->logger->info('No table map found for table ID: ' . $tableId);

        return null;
    }

    protected function getValues(): array
    {
        // if we don't get columns from information schema we don't know how to assign them
        if ($this->currentTableMap === null || $this->currentTableMap->columnDTOCollection->isEmpty()) {
            return [];
        }

        $binaryData = $this->binaryDataReader->read(
            $this->getColumnsBinarySize($this->currentTableMap->columnsAmount)
        );

        $values = [];
        while (!$this->binaryDataReader->isComplete($this->eventInfo->getSizeNoHeader())) {
            $values[] = $this->getColumnData($binaryData);
        }

        return $values;
    }

    protected function getColumnsBinarySize(int $columnsAmount): int
    {
        return (int)(($columnsAmount + 7) / 8);
    }

    protected function getColumnData(string $colsBitmap): array
    {
        if ($this->currentTableMap === null) {
            throw new RuntimeException('Current table map is missing!');
        }

        $values = [];

        // null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        // see http://dev.mysql.com/doc/internals/en/rows-event.html
        $nullBitmap = $this->binaryDataReader->read($this->getColumnsBinarySize($this->bitCount($colsBitmap)));
        $nullBitmapIndex = 0;

        foreach ($this->currentTableMap->columnDTOCollection as $i => $columnDTO) {
            $name = $columnDTO->getName();
            $type = $columnDTO->type;

            if ($this->bitGet($colsBitmap, $i) === 0) {
                $values[$name] = null;
                continue;
            }

            if ($this->checkNull($nullBitmap, $nullBitmapIndex)) {
                $values[$name] = null;
            } elseif ($type === ConstFieldType::IGNORE) {
                $this->binaryDataReader->advance($columnDTO->lengthSize);
                $values[$name] = null;
            } elseif ($type === ConstFieldType::TINY) {
                if ($columnDTO->isUnsigned()) {
                    $values[$name] = $this->binaryDataReader->readUInt8();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt8();
                }
            } elseif ($type === ConstFieldType::SHORT) {
                if ($columnDTO->isUnsigned()) {
                    $values[$name] = $this->binaryDataReader->readUInt16();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt16();
                }
            } elseif ($type === ConstFieldType::LONG) {
                if ($columnDTO->isUnsigned()) {
                    $values[$name] = $this->binaryDataReader->readUInt32();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt32();
                }
            } elseif ($type === ConstFieldType::LONGLONG) {
                if ($columnDTO->isUnsigned()) {
                    $values[$name] = $this->binaryDataReader->readUInt64();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt64();
                }
            } elseif ($type === ConstFieldType::INT24) {
                if ($columnDTO->isUnsigned()) {
                    $values[$name] = $this->binaryDataReader->readUInt24();
                } else {
                    $values[$name] = $this->binaryDataReader->readInt24();
                }
            } elseif ($type === ConstFieldType::FLOAT) {
                // http://dev.mysql.com/doc/refman/5.7/en/floating-point-types.html FLOAT(7,4)
                $values[$name] = round($this->binaryDataReader->readFloat(), 4);
            } elseif ($type === ConstFieldType::DOUBLE) {
                $values[$name] = $this->binaryDataReader->readDouble();
            } elseif ($type === ConstFieldType::VARCHAR || $type === ConstFieldType::STRING) {
                $values[$name] = $columnDTO->maxLength > 255 ? $this->getString(2) : $this->getString(1);
            } elseif ($type === ConstFieldType::NEWDECIMAL) {
                $values[$name] = $this->getDecimal($columnDTO);
            } elseif ($type === ConstFieldType::BLOB) {
                $values[$name] = $this->getString($columnDTO->lengthSize);
            } elseif ($type === ConstFieldType::DATETIME) {
                $values[$name] = $this->getDatetime();
            } elseif ($type === ConstFieldType::DATETIME2) {
                $values[$name] = $this->getDatetime2($columnDTO);
            } elseif ($type === ConstFieldType::TIMESTAMP) {
                $values[$name] = date('Y-m-d H:i:s', $this->binaryDataReader->readUInt32());
            } elseif ($type === ConstFieldType::TIME) {
                $values[$name] = $this->getTime();
            } elseif ($type === ConstFieldType::TIME2) {
                $values[$name] = $this->getTime2($columnDTO);
            } elseif ($type === ConstFieldType::TIMESTAMP2) {
                $values[$name] = $this->getTimestamp2($columnDTO);
            } elseif ($type === ConstFieldType::DATE) {
                $values[$name] = $this->getDate();
            } elseif ($type === ConstFieldType::YEAR) {
                // https://dev.mysql.com/doc/refman/5.7/en/year.html
                $year = $this->binaryDataReader->readUInt8();
                $values[$name] = $year === 0 ? null : 1900 + $year;
            } elseif ($type === ConstFieldType::ENUM) {
                $values[$name] = $this->getEnum($columnDTO);
            } elseif ($type === ConstFieldType::SET) {
                $values[$name] = $this->getSet($columnDTO);
            } elseif ($type === ConstFieldType::BIT) {
                $values[$name] = $this->getBit($columnDTO);
            } elseif ($type === ConstFieldType::GEOMETRY) {
                $values[$name] = $this->getString($columnDTO->lengthSize);
            } elseif ($type === ConstFieldType::JSON) {
                $values[$name] = JsonBinaryDecoderService::makeJsonBinaryDecoder(
                    $this->getString($columnDTO->lengthSize)
                )->parseToString();
            } else {
                throw new MySQLReplicationException('Unknown row type: ' . $type);
            }

            ++$nullBitmapIndex;
        }

        return $values;
    }

    protected function bitCount(string $bitmap): int
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

    protected function bitGet(string $bitmap, int $position): int
    {
        return $this->getBitFromBitmap($bitmap, $position) & (1 << ($position & 7));
    }

    protected function getBitFromBitmap(string $bitmap, int $position): int
    {
        $bit = $bitmap[(int)($position / 8)];
        if (is_string($bit)) {
            $bit = ord($bit);
        }

        return $bit;
    }

    protected function checkNull(string $nullBitmap, int $position): int
    {
        return $this->getBitFromBitmap($nullBitmap, $position) & (1 << ($position % 8));
    }

    protected function getString(int $size): string
    {
        return $this->binaryDataReader->readLengthString($size);
    }

    /**
     * Read MySQL's new decimal format introduced in MySQL 5
     * https://dev.mysql.com/doc/refman/5.6/en/precision-math-decimal-characteristics.html
     */
    protected function getDecimal(ColumnDTO $columnDTO): string
    {
        $digitsPerInteger = 9;
        $compressedBytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
        $integral = $columnDTO->precision - $columnDTO->decimals;
        $unCompIntegral = (int)($integral / $digitsPerInteger);
        $unCompFractional = (int)($columnDTO->decimals / $digitsPerInteger);
        $compIntegral = $integral - ($unCompIntegral * $digitsPerInteger);
        $compFractional = $columnDTO->decimals - ($unCompFractional * $digitsPerInteger);

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

        return bcmul($res, '1', $columnDTO->decimals);
    }

    protected function getDatetime(): ?string
    {
        $value = $this->binaryDataReader->readUInt64();
        // nasty mysql 0000-00-00 dates
        if ($value === '0') {
            return null;
        }

        return $this->dateFormatter($value);
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
     *
     * @link https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
     */
    protected function getDatetime2(ColumnDTO $columnDTO): ?string
    {
        $data = $this->binaryDataReader->readIntBeBySize(5);

        $yearMonth = $this->binaryDataReader->getBinarySlice($data, 1, 17, 40);

        $year = (int)($yearMonth / 13);
        $month = $yearMonth % 13;
        $day = $this->binaryDataReader->getBinarySlice($data, 18, 5, 40);
        $hour = $this->binaryDataReader->getBinarySlice($data, 23, 5, 40);
        $minute = $this->binaryDataReader->getBinarySlice($data, 28, 6, 40);
        $second = $this->binaryDataReader->getBinarySlice($data, 34, 6, 40);
        $fsp = $this->getFSP($columnDTO);

        $formattedDate = $this->dateFormatter(
            $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second
        );
        if ($formattedDate) {
            return $formattedDate . $fsp;
        }

        return null;
    }

    protected function dateFormatter(string $date): ?string
    {
        try {
            $dateTime = new DateTime($date);
        } catch (Exception) {
            $dateTime = DateTime::createFromFormat('YmdHis', $date);
            if ($dateTime === false) {
                return null;
            }
        }

        $formattedDate = $dateTime->format('Y-m-d H:i:s');
        if (DateTime::getLastErrors() !== false) {
            return null;
        }

        return $formattedDate;
    }

    /**
     * @link https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
     */
    protected function getFSP(ColumnDTO $columnDTO): string
    {
        $read = 0;
        $time = '';
        $fsp = $columnDTO->fsp;
        if ($fsp === 1 || $fsp === 2) {
            $read = 1;
        } elseif ($fsp === 3 || $fsp === 4) {
            $read = 2;
        } elseif ($fsp === 5 || $fsp === 6) {
            $read = 3;
        }
        if ($read > 0) {
            $microsecond = $this->binaryDataReader->readIntBeBySize($read);
            if ($fsp % 2) {
                $microsecond = (int)($microsecond / 10);
            }
            $time = $microsecond * (10 ** (6 - $fsp));
        }

        return (string)$time;
    }

    protected function getTime(): string
    {
        $data = $this->binaryDataReader->readUInt24();
        if ($data === 0) {
            return '00:00:00';
        }

        return sprintf('%s%02d:%02d:%02d', $data < 0 ? '-' : '', $data / 10000, ($data % 10000) / 100, $data % 100);
    }

    /**
     * TIME encoding for non-fractional part:
     * 1 bit sign    (1= non-negative, 0= negative)
     * 1 bit unused  (reserved for future extensions)
     * 10 bits hour   (0-838)
     * 6 bits minute (0-59)
     * 6 bits second (0-59)
     * ---------------------
     * 24 bits = 3 bytes
     */
    protected function getTime2(ColumnDTO $columnDTO): string
    {
        $data = $this->binaryDataReader->readInt24Be();

        $hour = $this->binaryDataReader->getBinarySlice($data, 2, 10, 24);
        $minute = $this->binaryDataReader->getBinarySlice($data, 12, 6, 24);
        $second = $this->binaryDataReader->getBinarySlice($data, 18, 6, 24);

        return (new DateTime())
                ->setTime($hour, $minute, $second)
                ->format('H:i:s') . $this->getFSP($columnDTO);
    }

    protected function getTimestamp2(ColumnDTO $columnDTO): string
    {
        $datetime = date('Y-m-d H:i:s', $this->binaryDataReader->readInt32Be());
        $fsp = $this->getFSP($columnDTO);
        if ($fsp !== '') {
            $datetime .= '.' . $fsp;
        }

        return $datetime;
    }

    protected function getDate(): ?string
    {
        $time = $this->binaryDataReader->readUInt24();
        if ($time === 0) {
            return null;
        }

        $year = ($time & ((1 << 15) - 1) << 9) >> 9;
        $month = ($time & ((1 << 4) - 1) << 5) >> 5;
        $day = ($time & ((1 << 5) - 1));
        if ($year === 0 || $month === 0 || $day === 0) {
            return null;
        }

        return (new DateTime())->setDate($year, $month, $day)
            ->format('Y-m-d');
    }

    protected function getEnum(ColumnDTO $columnDTO): string
    {
        $value = $this->binaryDataReader->readUIntBySize($columnDTO->size) - 1;

        // check if given value exists in enums, if there not existing enum mysql returns empty string.
        if (array_key_exists($value, $columnDTO->getEnumValues())) {
            return $columnDTO->getEnumValues()[$value];
        }

        return '';
    }

    protected function getSet(ColumnDTO $columnDTO): array
    {
        // we read set columns as a bitmap telling us which options are enabled
        $bitMask = $this->binaryDataReader->readUIntBySize($columnDTO->size);
        $sets = [];
        foreach ($columnDTO->getSetValues() as $k => $item) {
            if ($bitMask & (2 ** $k)) {
                $sets[] = $item;
            }
        }

        return $sets;
    }

    protected function getBit(ColumnDTO $columnDTO): string
    {
        $res = '';
        for ($byte = 0; $byte < $columnDTO->bytes; ++$byte) {
            $currentByte = '';
            $data = $this->binaryDataReader->readUInt8();
            if ($byte === 0) {
                if ($columnDTO->bytes === 1) {
                    $end = $columnDTO->bits;
                } else {
                    $end = $columnDTO->bits % 8;
                    if ($end === 0) {
                        $end = 8;
                    }
                }
            } else {
                $end = 8;
            }

            for ($bit = 0; $bit < $end; ++$bit) {
                if ($data & (1 << $bit)) {
                    $currentByte .= '1';
                } else {
                    $currentByte .= '0';
                }
            }
            $res .= strrev($currentByte);
        }

        return $res;
    }
}
