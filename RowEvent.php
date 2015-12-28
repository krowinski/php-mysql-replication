<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/7
 * Time: 下午9:15
 */
class RowEvent extends BinLogEvent
{


    public static function rowInit(BinLogPack $pack, $event_type, $size)
    {
        parent::_init($pack, $event_type, $size);
        self::$TABLE_ID = self::readTableId();

        if (in_array(self::$EVENT_TYPE, [ConstEventType::DELETE_ROWS_EVENT_V2, ConstEventType::WRITE_ROWS_EVENT_V2, ConstEventType::UPDATE_ROWS_EVENT_V2])) {
            self::$FLAGS = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA_LENGTH = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA = self::$PACK->read(self::$EXTRA_DATA_LENGTH / 8);

        } else {
            self::$FLAGS = unpack('S', self::$PACK->read(2))[1];
        }

        #Body
        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();
    }


    public static function tableMap(BinLogPack $pack, $event_type)
    {
        parent::_init($pack, $event_type);

        self::$TABLE_ID = self::readTableId();

        self::$FLAGS = unpack('S', self::$PACK->read(2))[1];

        $data = [];
        $data['schema_length'] = unpack("C", $pack->read(1))[1];

        $data['schema_name'] = self::$SCHEMA_NAME = $pack->read($data['schema_length']);

        // 00
        self::$PACK->advance(1);

        $data['table_length'] = unpack("C", self::$PACK->read(1))[1];
        $data['table_name'] = self::$TABLE_NAME = $pack->read($data['table_length']);

        // 00
        self::$PACK->advance(1);

        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();

        //
        $column_type_def = self::$PACK->read(self::$COLUMNS_NUM);

        // 避免重复读取 表信息
        if (self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['table_id'] == self::$TABLE_ID)
            return $data;

        self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME] = array(
            'schema_name' => $data['schema_name'],
            'table_name' => $data['table_name'],
            'table_id' => self::$TABLE_ID
        );


        self::$PACK->readCodedBinary();


        // fields 相应属性
        $colums = DbHelper::getFields($data['schema_name'], $data['table_name']);


        for ($i = 0; $i < strlen($column_type_def); $i++) {
            $type = ord($column_type_def[$i]);
            self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'][$i] = Columns::parse($type, $colums[$i], self::$PACK);

        }


        return $data;


    }

    public static function addRow(BinLogPack $pack, $event_type, $size)
    {
        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);

        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['add'] = self::_getAddRows($result, $len);
        return $value;
    }

    public static function delRow(BinLogPack $pack, $event_type, $size)
    {
        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);


        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['del'] = self::_getDelRows($result, $len);

        return $value;
    }

    public static function updateRow(BinLogPack $pack, $event_type, $size)
    {

        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap1'] = self::$PACK->read($len);

        $result['bitmap2'] = self::$PACK->read($len);


        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['update'] = self::_getUpdateRows($result, $len);

        return $value;
    }

    public static function BitGet($bitmap, $position)
    {
        $bit = $bitmap[intval($position / 8)];

        if (is_string($bit)) {

            $bit = ord($bit);
        }

        return $bit & (1 << ($position & 7));
    }

    public static function _is_null($null_bitmap, $position)
    {
        $bit = $null_bitmap[intval($position / 8)];
        if (is_string($bit)) {
            $bit = ord($bit);
        }


        return $bit & (1 << ($position % 8));
    }

    private static function _read_string($size, $column)
    {
        $string = self::$PACK->read_length_coded_pascal_string($size);
        if ($column['character_set_name']) {
            //string = string . decode(column . character_set_name)
        }
        return $string;
    }

    private static function _read_column_data($cols_bitmap, $len)
    {
        $values = [];

        //$l = (int)(($len * 8 + 7) / 8);
        $l = (int)((self::bitCount($cols_bitmap) + 7) / 8);

        # null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        # See http://dev.mysql.com/doc/internals/en/rows-event.html


        $null_bitmap = self::$PACK->read($l);

        $nullBitmapIndex = 0;
        foreach (self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'] as $i => $value) {
            $column = $value;
            $name = $value['name'];
            $unsigned = $value['unsigned'];


            if (self::BitGet($cols_bitmap, $i) == 0) {
                $values[$name] = null;
                continue;
            }

            if (self::_is_null($null_bitmap, $nullBitmapIndex)) {
                $values[$name] = null;
            } elseif ($column['type'] == FieldType::TINY) {
                if ($unsigned)
                    $values[$name] = unpack("C", self::$PACK->read(1))[1];
                else
                    $values[$name] = unpack("c", self::$PACK->read(1))[1];
            } elseif ($column['type'] == FieldType::SHORT) {
                if ($unsigned)
                    $values[$name] = unpack("S", self::$PACK->read(2))[1];
                else
                    $values[$name] = unpack("s", self::$PACK->read(2))[1];
            } elseif ($column['type'] == FieldType::LONG) {

                if ($unsigned) {
                    $values[$name] = unpack("I", self::$PACK->read(4))[1];
                } else {

                    $values[$name] = unpack("i", self::$PACK->read(4))[1];

                }
            } elseif ($column['type'] == FieldType::INT24) {
                if ($unsigned)
                    $values[$name] = self::$PACK->read_uint24();
                else
                    $values[$name] = self::$PACK->read_int24();
            } elseif ($column['type'] == FieldType::FLOAT)
                $values[$name] = unpack("f", self::$PACK->read(4))[1];
            elseif ($column['type'] == FieldType::DOUBLE)
                $values[$name] = unpack("d", self::$PACK->read(8))[1];
            elseif ($column['type'] == FieldType::VARCHAR ||
                $column['type'] == FieldType::STRING
            ) {
                if ($column['max_length'] > 255)
                    $values[$name] = self::_read_string(2, $column);
                else
                    $values[$name] = self::_read_string(1, $column);
            } elseif ($column['type'] == FieldType::NEWDECIMAL) {
                //$values[$name] = self.__read_new_decimal(column)
            } elseif ($column['type'] == FieldType::BLOB) {
                //ok
                //echo 'length_size->>>> '.($column['length_size'])."\n";
                $values[$name] = self::_read_string($column['length_size'], $column);

            }
            elseif ($column['type'] == FieldType::DATETIME) {

                $values[$name] = self::_read_datetime();
            } elseif ($column['type'] == FieldType::DATETIME2) {
                //ok
                $values[$name] = self::_read_datetime2($column);
            }elseif ($column['type'] == FieldType::TIME2) {

                $values[$name] = self::_read_time2($column);
            }
            elseif ($column['type'] == FieldType::TIMESTAMP2){
                //ok
                $time = date('Y-m-d H:i:m',self::$PACK->read_int_be_by_size(4));
                // 微妙
                $time .= '.' . self::_add_fsp_to_time($column);
                $values[$name] = $time;
            }
                /*
            elseif ($column['type'] == FieldType::TIME:
                $values[$name] = self.__read_time()
            elseif ($column['type'] == FieldType::DATE:
                $values[$name] = self.__read_date()
            elseif ($column['type'] == FieldType::TIMESTAMP:
                $values[$name] = datetime.datetime.fromtimestamp(
                        self::$PACK->read_uint32())

            # For new date format:

            elseif ($column['type'] == FieldType::TIME2:
                $values[$name] = self.__read_time2(column)
            elseif ($column['type'] == FieldType::TIMESTAMP2:
                $values[$name] = self.__add_fsp_to_time(
                        datetime.datetime.fromtimestamp(
                            self::$PACK->read_int_be_by_size(4)), column)
            */
            elseif ($column['type'] == FieldType::LONGLONG) {
                if ($unsigned)
                    $values[$name] = self::$PACK->readUint64();
                else
                    $values[$name] = self::$PACK->readInt64();
            }
            /*
            elseif ($column['type'] == FieldType::YEAR:
                $values[$name] = self::$PACK->read_uint8() + 1900
            elseif ($column['type'] == FieldType::ENUM:
                $values[$name] = column.enum_values[
                    self::$PACK->read_uint_by_size(column.size) - 1]
            elseif ($column['type'] == FieldType::SET:
                # We read set columns as a bitmap telling us which options
                # are enabled
                bit_mask = self::$PACK->read_uint_by_size(column.size)
                $values[$name] = set(
                    val for idx, val in enumerate(column.set_values)
                if bit_mask & 2 ** idx
                ) or None

            elseif ($column['type'] == FieldType::BIT:
                $values[$name] = self.__read_bit(column)
            elseif ($column['type'] == FieldType::GEOMETRY:
                $values[$name] = self::$PACK->read_length_coded_pascal_string(
                        column.length_size)
            else:
                raise NotImplementedError("Unknown MySQL column type: %d" %
                    (column.type))
            */
            $nullBitmapIndex += 1;
        }
        $values['table_name'] = self::$TABLE_NAME;
        return $values;
    }


    private static function _read_datetime()
    {
        $value = self::$PACK->readUint64();
        if ($value == 0)  # nasty mysql 0000-00-00 dates
            return null;

        $date = $value / 1000000;
        $time = (int)($value % 1000000);

        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year == 0 or $month == 0 or $day == 0)
            return null;

        var_dump($year, $month, $date);exit;
//
//        $date = datetime.datetime(
//        year=year,
//        month=month,
//        day=day,
//        hour=int(time / 10000),
//        minute=int((time % 10000) / 100),
//        second=int(time % 100))
        return $date;

    }

    private static function  _read_datetime2($column) {
        /*DATETIME

        1 bit  sign           (1= non-negative, 0= negative)
        17 bits year*13+month  (year 0-9999, month 0-12)
         5 bits day            (0-31)
         5 bits hour           (0-23)
         6 bits minute         (0-59)
         6 bits second         (0-59)
        ---------------------------
        40 bits = 5 bytes
        */
        $data = self::$PACK->read_int_be_by_size(5);

        $year_month = self::_read_binary_slice($data, 1, 17, 40);


        $year=(int)($year_month / 13);
        $month=$year_month % 13;
        $day=self::_read_binary_slice($data, 18, 5, 40);
        $hour=self::_read_binary_slice($data, 23, 5, 40);
        $minute=self::_read_binary_slice($data, 28, 6, 40);
        $second=self::_read_binary_slice($data, 34, 6, 40);
        if($hour < 10) {
            $hour ='0'.$hour;
        }
        if($minute < 10) {
            $minute = '0'.$minute;
        }
        if($second < 10) {
            $second = '0'.$second;
        }
        $time = $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second;
        $microsecond = self::_add_fsp_to_time($column);
        if($microsecond) {
            $time .='.'.$microsecond;
        }
        return $time;
    }

    private static function _read_binary_slice($binary, $start, $size, $data_length) {
        /*
        Read a part of binary data and extract a number
        binary: the data
        start: From which bit (1 to X)
        size: How many bits should be read
        data_length: data size
        */
        $binary = $binary >> $data_length - ($start + $size);
        $mask = ((1 << $size) - 1);
        return $binary & $mask;
    }

    private static function _add_fsp_to_time($column)
    {
        /*Read and add the fractional part of time
            For more details about new date format:
            http://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
            */


        $read = 0;
        $time = '';
        if( $column['fsp'] == 1 or $column['fsp'] == 2)
            $read = 1;
        elseif($column['fsp'] == 3 or $column['fsp'] == 4)
            $read = 2;
        elseif ($column ['fsp'] == 5 or $column['fsp'] == 6)
            $read = 3;
        if ($read > 0) {
            $microsecond = self::$PACK->read_int_be_by_size($read);
            if ($column['fsp'] % 2)
                $time = (int)($microsecond / 10);
            else
                $time = $microsecond;
        }
        return $time;
    }




    private static function _getUpdateRows($result, $len) {
        $rows = [];
        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {

            $value['beform'] = self::_read_column_data($result['bitmap1'], $len);
            $value['after'] = self::_read_column_data($result['bitmap2'], $len);
            $rows[] = $value;
        }
        return $rows;
    }

    private static function _getDelRows($result, $len) {
        $rows = [];
        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {
            $rows[] = self::_read_column_data($result['bitmap'], $len);
        }
        return $rows;
    }

    private static function  _getAddRows($result, $len) {
        $rows = [];

        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {
            $rows[] = self::_read_column_data($result['bitmap'], $len);
        }
        return $rows;
    }
}