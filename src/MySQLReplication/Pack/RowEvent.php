<?php

namespace MySQLReplication\Pack;

use MySQLReplication\BinLog\BinLogColumns;
use MySQLReplication\BinLog\BinLogEvent;
use MySQLReplication\BinLog\BinLogPack;
use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\DTO\DeleteRowsDTO;
use MySQLReplication\DTO\TableMapDTO;
use MySQLReplication\DTO\UpdateRowsDTO;
use MySQLReplication\DTO\WriteRowsDTO;
use MySQLReplication\Exception\BinLogException;

/**
 * Class RowEvent
 */
class RowEvent extends BinLogEvent
{
    /**
     * @var []
     */
    private static $fields;
    /**
     * @var bool
     */
    private static $process = true;

    /**
     * This evenement describe the structure of a table.
     * It's send before a change append on a table.
     * A end user of the lib should have no usage of this
     *
     * @param BinLogPack $pack
     * @param DBHelper $DBHelper
     * @param $eventInfo
     * @param $size
     * @return array
     */
    public static function tableMap(BinLogPack $pack, DBHelper $DBHelper, $eventInfo, $size)
    {
        parent::init($pack, $eventInfo['type']);

        self::$TABLE_ID = self::readTableId();

        self::$FLAGS = unpack('S', self::$BinLogPack->read(2))[1];

        $data = [];
        $data['schema_length'] = unpack('C', $pack->read(1))[1];
        $data['schema_name'] = self::$SCHEMA_NAME = $pack->read($data['schema_length']);

        self::$BinLogPack->advance(1);

        $data['table_length'] = unpack('C', self::$BinLogPack->read(1))[1];
        $data['table_name'] = self::$TABLE_NAME = $pack->read($data['table_length']);

        self::$BinLogPack->advance(1);

        self::$COLUMNS_NUM = self::$BinLogPack->readCodedBinary();

        $column_type_def = self::$BinLogPack->read(self::$COLUMNS_NUM);

        // automatyczne czyszczenie starych danych
        if (count(self::$TABLE_MAP) >= 200) {
            self::$TABLE_MAP = array_slice(self::$TABLE_MAP, 100, -1, true);
        }

        $tableMapDTO = new TableMapDTO(
            $eventInfo['date'],
            $eventInfo['pos'],
            $eventInfo['size'],
            $size,
            self::$TABLE_ID,
            $data['schema_name'],
            $data['table_name'],
            self::$COLUMNS_NUM
        );

        if (isset(self::$TABLE_MAP[self::$TABLE_ID]))
        {
            return $tableMapDTO;
        }

        self::$BinLogPack->readCodedBinary();

        $columns = $DBHelper->getFields($data['schema_name'], $data['table_name']);

        self::$TABLE_MAP[self::$TABLE_ID]['fields'] = [];
        self::$TABLE_MAP[self::$TABLE_ID]['database'] = $data['schema_name'];
        self::$TABLE_MAP[self::$TABLE_ID]['table_name'] = $data['table_name'];

        // if you drop tables and parse of logs you will get empty scheme
        if (empty($columns))
        {
            return null;
        }

        for ($i = 0; $i < strlen($column_type_def); $i++)
        {
            // this a dirty hack to prevent row events containing columns which have been dropped prior
            // to pymysqlreplication start, but replayed from binlog from blowing up the service.
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

            self::$TABLE_MAP[self::$TABLE_ID]['fields'][$i] = BinLogColumns::parse($type, $columns[$i], self::$BinLogPack);
        }

        return $tableMapDTO;
    }

    /**
     * @param BinLogPack $pack
     * @param array $eventInfo
     * @param $size
     * @param $onlyTables
     * @param $onlyDatabases
     * @return mixed
     */
    public static function addRow(BinLogPack $pack, array $eventInfo, $size, $onlyTables, $onlyDatabases)
    {
        self::rowInit($pack, $eventInfo['type'], $size, $onlyTables, $onlyDatabases);

        if (false === self::$process)
        {
            return null;
        }

        $values = self::_getAddRows(['bitmap' => self::$BinLogPack->read(self::getColumnsAmount(self::$COLUMNS_NUM))]);

        return new WriteRowsDTO(
            $eventInfo['date'],
            $eventInfo['pos'],
            $eventInfo['size'],
            $size,
            self::$SCHEMA_NAME,
            self::$TABLE_NAME,
            self::$COLUMNS_NUM,
            count($values),
            $values
        );
    }

    /**
     * @param BinLogPack $pack
     * @param $event_type
     * @param $size
     * @param array $onlyTables
     * @param array $onlyDatabases
     */
    private static function rowInit(BinLogPack $pack, $event_type, $size, array $onlyTables, array $onlyDatabases)
    {
        self::$process = true;

        parent::init($pack, $event_type, $size);

        self::$TABLE_ID = self::readTableId();

        self::$FLAGS = self::$BinLogPack->readUIntBySize(2);

        if (in_array(self::$EVENT_TYPE, [
            ConstEventType::DELETE_ROWS_EVENT_V2,
            ConstEventType::WRITE_ROWS_EVENT_V2,
            ConstEventType::UPDATE_ROWS_EVENT_V2
        ]))
        {
            self::$EXTRA_DATA_LENGTH = self::$BinLogPack->readUIntBySize(2);

            self::$EXTRA_DATA = self::$BinLogPack->read(self::$EXTRA_DATA_LENGTH / 8);
        }

        self::$COLUMNS_NUM = self::$BinLogPack->readCodedBinary();


        self::$fields = [];
        if (self::$TABLE_MAP[self::$TABLE_ID])
        {
            self::$fields = self::$TABLE_MAP[self::$TABLE_ID]['fields'];

            if (!empty($onlyTables) && !in_array(self::$TABLE_MAP[self::$TABLE_ID]['table_name'], $onlyTables))
            {
                self::$process = false;
            }

            if (!empty($onlyTables) && !in_array(self::$TABLE_MAP[self::$TABLE_ID]['database'], $onlyDatabases))
            {
                self::$process = false;
            }
        }
        if ([] == self::$fields)
        {
            //remove cache can be empty (drop table)
            unset(self::$TABLE_MAP[self::$TABLE_ID]);

            self::$process = false;
        }
    }

    /**
     * @param int $columns
     * @return int
     */
    private static function getColumnsAmount($columns)
    {
        return (int)(($columns + 7) / 8);
    }

    /**
     * @param array $result
     * @return array
     */
    private static function _getAddRows(array $result)
    {
        $rows = [];
        while (!self::$BinLogPack->isComplete(self::$PACK_SIZE))
        {
            $rows[] = self::_read_column_data($result['bitmap']);
        }

        return $rows;
    }

    /**
     * @param $cols_bitmap
     * @return array
     * @throws \Exception
     */
    private static function _read_column_data($cols_bitmap)
    {
        $values = [];

        $l = self::getColumnsAmount(self::bitCount($cols_bitmap));

        // null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        // see http://dev.mysql.com/doc/internals/en/rows-event.html
        $null_bitmap = self::$BinLogPack->read($l);
        $nullBitmapIndex = 0;

        foreach (self::$fields as $i => $column)
        {
            $name = $column['name'];
            $unsigned = $column['unsigned'];

            if (self::bitGet($cols_bitmap, $i) == 0)
            {
                $values[$name] = null;
                continue;
            }

            if (self::_is_null($null_bitmap, $nullBitmapIndex))
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
                    $values[$name] = unpack('C', self::$BinLogPack->read(1))[1];
                }
                else
                {
                    $values[$name] = unpack('c', self::$BinLogPack->read(1))[1];
                }
            }
            elseif ($column['type'] == ConstFieldType::SHORT)
            {
                if ($unsigned)
                {
                    $values[$name] = unpack('v', self::$BinLogPack->read(2))[1];
                }
                else
                {
                    $values[$name] = unpack('s', self::$BinLogPack->read(2))[1];
                }
            }
            elseif ($column['type'] == ConstFieldType::LONG)
            {
                if ($unsigned)
                {
                    $values[$name] = unpack('I', self::$BinLogPack->read(4))[1];
                }
                else
                {
                    $values[$name] = unpack('i', self::$BinLogPack->read(4))[1];
                }
            }
            elseif ($column['type'] == ConstFieldType::INT24)
            {
                if ($unsigned)
                {
                    $values[$name] = self::$BinLogPack->readUInt24();
                }
                else
                {
                    $values[$name] = self::$BinLogPack->readInt24();
                }
            }
            elseif ($column['type'] == ConstFieldType::FLOAT)
            {
                // http://dev.mysql.com/doc/refman/5.7/en/floating-point-types.html FLOAT(7,4)
                $values[$name] = round(unpack('f', self::$BinLogPack->read(4))[1], 4);
            }
            elseif ($column['type'] == ConstFieldType::DOUBLE)
            {
                $values[$name] = unpack('d', self::$BinLogPack->read(8))[1];
            }
            elseif ($column['type'] == ConstFieldType::VARCHAR || $column['type'] == ConstFieldType::STRING)
            {
                if ($column['max_length'] > 255)
                {
                    $values[$name] = self::_read_string(2, $column);
                }
                else
                {
                    $values[$name] = self::_read_string(1, $column);
                }
            }
            elseif ($column['type'] == ConstFieldType::NEWDECIMAL)
            {
                $values[$name] = self::__read_new_decimal($column);
            }
            elseif ($column['type'] == ConstFieldType::BLOB)
            {
                $values[$name] = self::_read_string($column['length_size'], $column);
            }
            elseif ($column['type'] == ConstFieldType::DATETIME)
            {
                $values[$name] = self::_read_datetime();
            }
            elseif ($column['type'] == ConstFieldType::DATETIME2)
            {
                $values[$name] = self::_read_datetime2($column);
            }
            elseif ($column['type'] == ConstFieldType::TIME2)
            {
                $values[$name] = self::_read_time2($column);
            }
            elseif ($column['type'] == ConstFieldType::TIMESTAMP2)
            {
                $time = date('Y-m-d H:i:s', self::$BinLogPack->readIntBeBySize(4));
                $fsp = self::_add_fsp_to_time($column);
                if ('' !== $fsp)
                {
                    $time .= '.' . $fsp;
                }
                $values[$name] = $time;
            }
            elseif ($column['type'] == ConstFieldType::DATE)
            {
                $values[$name] = self::_read_date();
            }
            elseif ($column['type'] == ConstFieldType::LONGLONG)
            {
                if ($unsigned)
                {
                    $values[$name] = self::$BinLogPack->readUInt64();
                }
                else
                {
                    $values[$name] = self::$BinLogPack->readInt64();
                }
            }
            elseif ($column['type'] == ConstFieldType::YEAR)
            {
                $values[$name] = self::$BinLogPack->readUInt8() + 1900;
            }
            elseif ($column['type'] == ConstFieldType::ENUM)
            {
                $values[$name] = $column['enum_values'][self::$BinLogPack->readUIntBySize($column['size']) - 1];
            }
            elseif ($column['type'] == ConstFieldType::SET)
            {
                // we read set columns as a bitmap telling us which options are enabled
                $bit_mask = self::$BinLogPack->readUIntBySize($column['size']);
                $sets = [];
                foreach ($column['set_values'] as $k => $item)
                {
                    if ($bit_mask & pow(2, $k))
                    {
                        $sets[] = $item;
                    }
                }
                $values[$name] = $sets;
            }
            elseif ($column['type'] == ConstFieldType::BIT)
            {
                $values[$name] = self::_read_bit($column);
            }
            elseif ($column['type'] == ConstFieldType::GEOMETRY)
            {
                $values[$name] = self::$BinLogPack->readLengthCodedPascalString($column['length_size']);
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
     * @param $bitmap
     * @param $position
     * @return int
     */
    private static function bitGet($bitmap, $position)
    {
        $bit = $bitmap[(int)($position / 8)];
        if (is_string($bit))
        {
            $bit = ord($bit);
        }

        return $bit & (1 << ($position & 7));
    }

    /**
     * @param $null_bitmap
     * @param $position
     * @return int
     */
    private static function _is_null($null_bitmap, $position)
    {
        $bit = $null_bitmap[intval($position / 8)];
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
    private static function _read_string($size, array $column)
    {
        $string = self::$BinLogPack->readLengthCodedPascalString($size);
        if ($column['character_set_name'])
        {
            // convert strings?
        }

        return $string;
    }

    /**
     * Read MySQL's new decimal format introduced in MySQL 5
     * @param $column
     * @return string
     */
    private static function __read_new_decimal(array $column)
    {
        $digits_per_integer = 9;
        $compressed_bytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
        $integral = $column['precision'] - $column['decimals'];
        $uncomp_integral = (int)($integral / $digits_per_integer);
        $uncomp_fractional = (int)($column['decimals'] / $digits_per_integer);
        $comp_integral = $integral - ($uncomp_integral * $digits_per_integer);
        $comp_fractional = $column['decimals'] - ($uncomp_fractional * $digits_per_integer);

        $value = self::$BinLogPack->readUInt8();
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
        self::$BinLogPack->unread(pack('C', ($value ^ 0x80)));

        $size = $compressed_bytes[$comp_integral];
        if ($size > 0)
        {
            $value = self::$BinLogPack->readIntBeBySize($size) ^ $mask;
            $res .= $value;
        }

        for ($i = 0; $i < $uncomp_integral; $i++)
        {
            $value = self::$BinLogPack->readIntBeBySize(4) ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $res .= '.';

        for ($i = 0; $i < $uncomp_fractional; $i++)
        {
            $value = self::$BinLogPack->readIntBeBySize(4) ^ $mask;
            $res .= sprintf('%09d', $value);
        }

        $size = $compressed_bytes[$comp_fractional];
        if ($size > 0)
        {
            $value = self::$BinLogPack->readIntBeBySize($size) ^ $mask;
            $res .= sprintf('%0' . $comp_fractional . 'd', $value);
        }

        return bcmul($res, 1, $column['precision']);
    }

    /**
     * @return float|null
     */
    private static function _read_datetime()
    {
        $value = self::$BinLogPack->readUInt64();
        if ($value == 0)  # nasty mysql 0000-00-00 dates
        {
            return null;
        }

        $date = $value / 1000000;
        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year == 0 || $month == 0 || $day == 0)
        {
            return '';
        }

        $date = new \DateTime();
        $date->setDate($year, $month, $day);
        return $date->format('Y-m-d');
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
     * @param $column
     * @return string
     * @throws \Exception
     */
    private static function _read_datetime2(array $column)
    {
        $data = self::$BinLogPack->readIntBeBySize(5);

        $year_month = self::_read_binary_slice($data, 1, 17, 40);

        $year = (int)($year_month / 13);
        $month = $year_month % 13;
        $day = self::_read_binary_slice($data, 18, 5, 40);
        $hour = self::_read_binary_slice($data, 23, 5, 40);
        $minute = self::_read_binary_slice($data, 28, 6, 40);
        $second = self::_read_binary_slice($data, 34, 6, 40);

        $date = new \DateTime($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second);
        if (array_sum($date->getLastErrors()) > 0)
        {
            return null;
        }
        return $date->format('Y-m-d H:i:s') . self::_add_fsp_to_time($column);
    }

    /**
     * Read a part of binary data and extract a number
     * binary: the data
     * start: From which bit (1 to X)
     * size: How many bits should be read
     * data_length: data size
     *
     * @param $binary
     * @param $start
     * @param $size
     * @param $data_length
     * @return int
     */
    private static function _read_binary_slice($binary, $start, $size, $data_length)
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
    private static function _add_fsp_to_time(array $column)
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
            $microsecond = self::$BinLogPack->readIntBeBySize($read);
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
    private static function _read_time2(array $column)
    {
        $data = self::$BinLogPack->readIntBeBySize(3);

        $hour = self::_read_binary_slice($data, 2, 10, 24);
        $minute = self::_read_binary_slice($data, 12, 6, 24);
        $second = self::_read_binary_slice($data, 18, 6, 24);

        $date = new \DateTime();
        $date->setTime($hour, $minute, $second);

        return $date->format('H:i:s') . self::_add_fsp_to_time($column);
    }

    /**
     * @return string
     */
    private static function _read_date()
    {
        $time = self::$BinLogPack->readUInt24();
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

        $date = new \DateTime();
        $date->setDate($year, $month, $day);
        return $date->format('Y-m-d');
    }

    /**
     * Read MySQL BIT type
     * @param array $column
     * @return string
     */
    private static function _read_bit(array $column)
    {
        $res = '';
        for ($byte = 0; $byte < $column['bytes']; $byte++)
        {
            $current_byte = '';
            $data = self::$BinLogPack->readUInt8();
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
     * @param BinLogPack $pack
     * @param $eventInfo
     * @param $size
     * @param array $onlyTables
     * @param array $onlyDatabases
     * @return mixed
     */
    public static function delRow(BinLogPack $pack, $eventInfo, $size, array $onlyTables, array $onlyDatabases)
    {
        self::rowInit($pack, $eventInfo['type'], $size, $onlyTables, $onlyDatabases);

        if (false === self::$process)
        {
            return null;
        }

        $values = self::_getDelRows(['bitmap' => self::$BinLogPack->read(self::getColumnsAmount(self::$COLUMNS_NUM))]);

        return new DeleteRowsDTO(
            $eventInfo['date'],
            $eventInfo['pos'],
            $eventInfo['size'],
            $size,
            self::$SCHEMA_NAME,
            self::$TABLE_NAME,
            self::$COLUMNS_NUM,
            count($values),
            $values
        );
    }

    /**
     * @param array $result
     * @return array
     */
    private static function _getDelRows(array $result)
    {
        $rows = [];
        while (!self::$BinLogPack->isComplete(self::$PACK_SIZE))
        {
            $rows[] = self::_read_column_data($result['bitmap']);
        }

        return $rows;
    }

    /**
     * @param BinLogPack $pack
     * @param array $eventInfo
     * @param $size
     * @param array $onlyTables
     * @param array $onlyDatabases
     * @return mixed
     */
    public static function updateRow(BinLogPack $pack, array $eventInfo, $size, array $onlyTables, array $onlyDatabases)
    {
        self::rowInit($pack, $eventInfo['type'], $size, $onlyTables, $onlyDatabases);

        if (false === self::$process)
        {
            return null;
        }

        $len = self::getColumnsAmount(self::$COLUMNS_NUM);

        $values = self::_getUpdateRows(['bitmap1' => self::$BinLogPack->read($len), 'bitmap2' => self::$BinLogPack->read($len)]);

        return new UpdateRowsDTO(
            $eventInfo['date'],
            $eventInfo['pos'],
            $eventInfo['size'],
            $size,
            self::$SCHEMA_NAME,
            self::$TABLE_NAME,
            self::$COLUMNS_NUM,
            count($values),
            $values

        );
    }

    /**
     * @param array $result
     * @return array
     */
    private static function _getUpdateRows(array $result)
    {
        $rows = [];
        while (!self::$BinLogPack->isComplete(self::$PACK_SIZE))
        {
            $rows[] = [
                'before' => self::_read_column_data($result['bitmap1']),
                'after' => self::_read_column_data($result['bitmap2'])
            ];
        }

        return $rows;
    }
}
