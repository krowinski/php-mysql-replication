<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/7
 * Time: 下午8:48
 */
class Columns {

    private static $field;

    public static function parse($column_type, $column_schema, $packet) {

        self::$field = [];

        self::$field['type'] = $column_type;
        self::$field['name'] = $column_schema["COLUMN_NAME"];
        self::$field['collation_name'] = $column_schema["COLLATION_NAME"];
        self::$field['character_set_name'] = $column_schema["CHARACTER_SET_NAME"];
        self::$field['comment'] = $column_schema["COLUMN_COMMENT"];
        self::$field['unsigned'] = stripos($column_schema["COLUMN_TYPE"], 'unsigned') === false ? false : true;
        self::$field['type_is_bool'] = false;
        self::$field['is_primary'] = $column_schema["COLUMN_KEY"] == "PRI";

        if (self::$field['type'] == FieldType::VARCHAR) {
            self::$field['max_length'] = unpack('s', $packet->read(2))[1];
        }elseif (self::$field['type'] == FieldType::DOUBLE){
            self::$field['size'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::FLOAT){
            self::$field['size'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::TIMESTAMP2){
            self::$field['fsp'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::DATETIME2){
            self::$field['fsp']= $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::TIME2) {
            self::$field['fsp'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::TINY && $column_schema["COLUMN_TYPE"] == "tinyint(1)") {
            self::$field['type_is_bool'] = True;
        }elseif (self::$field['type'] == FieldType::VAR_STRING || self::$field['type'] == FieldType::STRING){
            self::_read_string_metadata($packet, $column_schema);
        }elseif( self::$field['type'] == FieldType::BLOB){
            self::$field['length_size'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::GEOMETRY){
            self::$field['length_size'] = $packet->readUint8();
        }elseif( self::$field['type'] == FieldType::NEWDECIMAL){
            self::$field['precision'] = $packet->readUint8();
            self::$field['decimals'] = $packet->readUint8();
        }elseif (self::$field['type'] == FieldType::BIT) {
            $bits = $packet->readUint8();
            $bytes = $packet->readUint8();
            self::$field['bits'] = ($bytes * 8) + $bits;
            self::$field['bytes'] = int((self::$field['bits'] + 7) / 8);
        }
        return self::$field;
    }

    private static function _read_string_metadata($packet, $column_schema){

        $metadata = ($packet->readUint8() << 8) + $packet->readUint8();
        $real_type = $metadata >> 8;
        if($real_type == FieldType::SET || $real_type == FieldType::ENUM) {
            self::$field['type'] = $real_type;
            self::$field['size'] = $metadata & 0x00ff;
            self::_read_enum_metadata($column_schema);
        } else {
            self::$field['max_length'] = ((($metadata >> 4) & 0x300) ^ 0x300) + ($metadata & 0x00ff);
        }
    }
    private static function _read_enum_metadata($column_schema) {
        $enums = $column_schema["COLUMN_TYPE"];
        if (self::$field['type'] == FieldType::ENUM) {
            $enums = str_replace('enum(', '', $enums);
            $enums = str_replace(')', '', $enums);
            $enums = str_replace('\'', '', $enums);
            self::$field['enum_values'] = explode(',', $enums);
        } else {
            $enums = str_replace('set(', '', $enums);
            $enums = str_replace(')', '', $enums);
            $enums = str_replace('\'', '', $enums);
            self::$field['set_values'] = explode(',', $enums);
        }
    }

}