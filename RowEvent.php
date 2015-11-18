<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/7
 * Time: 下午9:15
 */
class RowEvent extends BinLogEvent {


    public static function rowInit(BinLogPack $pack, $event_type) {
        parent::_init($pack, $event_type);
        self::$TABLE_ID = self::readTableId();

        if(in_array(self::$EVENT_TYPE,[30,31,32])) {
            self::$FLAGS    = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA_LENGTH = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA = self::$PACK->read(self::$EXTRA_DATA_LENGTH/8);

        } else{
            self::$FLAGS    = unpack('S', self::$PACK->read(2))[1];
        }

        #Body
        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();
    }



    public static function tableMap(BinLogPack $pack, $event_type) {
        parent::_init($pack, $event_type);

        self::$TABLE_ID = self::readTableId();

        self::$FLAGS    = unpack('S', self::$PACK->read(2))[1];

        $data = [];
        $data['schema_length'] = unpack("C", $pack->read(1))[1];

        $data['schema_name'] = $pack->read($data['schema_length']);

        // 00
        self::$PACK->advance(1);

        $data['table_length'] = unpack("C", self::$PACK->read(1))[1];
        $data['table_name'] = $pack->read($data['table_length']);

        // 00
        self::$PACK->advance(1);

        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();

        //
        $column_type_def   = self::$PACK->read(self::$COLUMNS_NUM);


        self::$TABLE_MAP[self::$TABLE_ID]['schema_name'] = $data['schema_name'];
        self::$TABLE_MAP[self::$TABLE_ID]['table_name'] = $data['table_name'];




        //var_dump(self::$_table_map);return;





        self::$TABLE_MAP[self::$TABLE_ID]['init'] = true;

        self::$PACK->readCodedBinary();


        // fields 相应属性
        $colums = DbHelper::getFields($data['schema_name'], $data['table_name']);


        for($i=0;$i<strlen($column_type_def);$i++) {
            $type = ord($column_type_def[$i]);
            self::$TABLE_MAP[self::$TABLE_ID]['fields'][$i] = Columns::parse($type, $colums[$i], self::$PACK);

        }


        return $data;


    }

    public static function addRow(BinLogPack $pack, $event_type) {
        self::rowInit($pack, $event_type);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);



        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['now'] = self::_read_column_data($result['bitmap'],$len);

        var_dump($value);

        return $result;
    }

    public static function delRow(BinLogPack $pack, $event_type) {
        self::rowInit($pack, $event_type);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);



        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['del'] = self::_read_column_data($result['bitmap'],$len);

        var_dump($value);

        return $result;
    }

    public static function updateRow(BinLogPack $pack, $event_type) {

        self::rowInit($pack, $event_type);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap1'] = self::$PACK->read($len);

        $result['bitmap2'] = self::$PACK->read($len);


        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['beform'] = self::_read_column_data($result['bitmap1'],$len);
        $value['after'] = self::_read_column_data($result['bitmap2'],$len);

        var_dump($value);

        return $result;
    }

    public static function BitGet($bitmap, $position) {
        $bit = $bitmap[intval($position / 8)];

        if(is_string($bit)) {

            $bit = ord($bit);
        }

        return $bit & (1 << ($position & 7));
    }

    public static function _is_null( $null_bitmap, $position)
    {
        $bit = $null_bitmap[intval($position / 8)];
        if (is_string($bit)){
            $bit = ord($bit);
        }


        return $bit & (1 << ($position % 8));
    }

    private static function _read_string($size, $column){
        $string = self::$PACK->read_length_coded_pascal_string($size);
        if ($column['character_set_name'])
            //string = string . decode(column . character_set_name)
        return $string;
    }

    private static function _read_column_data($cols_bitmap, $len) {
        $values = [];

        //$l = (int)(($len * 8 + 7) / 8);
        $l = (int)((self::bitCount($cols_bitmap) + 7) / 8);

        # null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        # See http://dev.mysql.com/doc/internals/en/rows-event.html



        $null_bitmap = self::$PACK->read($l);

        $nullBitmapIndex = 0;
        foreach(self::$TABLE_MAP[self::$TABLE_ID]['fields'] as $i => $value) {
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
                }
                else {

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
            } elseif ($column['type'] == FieldType::BLOB)
                $values[$name] = self::_read_string($column['length_size'], $column);
            elseif ($column['type'] == FieldType::DATETIME) {
                //$values[$name] = self::_read_datetime();
            } /*
            elseif ($column['type'] == FieldType::TIME:
                $values[$name] = self.__read_time()
            elseif ($column['type'] == FieldType::DATE:
                $values[$name] = self.__read_date()
            elseif ($column['type'] == FieldType::TIMESTAMP:
                $values[$name] = datetime.datetime.fromtimestamp(
                        self::$PACK->read_uint32())

            # For new date format:
            elseif ($column['type'] == FieldType::DATETIME2:
                $values[$name] = self.__read_datetime2(column)
            elseif ($column['type'] == FieldType::TIME2:
                $values[$name] = self.__read_time2(column)
            elseif ($column['type'] == FieldType::TIMESTAMP2:
                $values[$name] = self.__add_fsp_to_time(
                        datetime.datetime.fromtimestamp(
                            self::$PACK->read_int_be_by_size(4)), column)
            */
            elseif ($column['type'] == FieldType::LONGLONG)
                if ($unsigned)
                    $values[$name] = self::$PACK->readUint64();
                else
                    $values[$name] = self::$PACK->readInt64();
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

        return $values;
    }

}