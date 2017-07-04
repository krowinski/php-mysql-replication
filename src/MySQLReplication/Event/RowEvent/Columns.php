<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;

/**
 * Class Columns
 * @package MySQLReplication\Event\RowEvent
 */
class Columns
{
    /**
     * @var array
     */
    private static $field;

    /**
     * @param string $columnType
     * @param array $columnSchema
     * @param BinaryDataReader $binaryDataReader
     * @return array
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    public static function parse($columnType, array $columnSchema, BinaryDataReader $binaryDataReader)
    {
        self::$field = [];
        self::$field['type'] = $columnType;
        self::$field['name'] = $columnSchema['COLUMN_NAME'];
        self::$field['collation_name'] = $columnSchema['COLLATION_NAME'];
        self::$field['character_set_name'] = $columnSchema['CHARACTER_SET_NAME'];
        self::$field['comment'] = $columnSchema['COLUMN_COMMENT'];
        self::$field['unsigned'] = !(stripos($columnSchema['COLUMN_TYPE'], 'unsigned') === false);
        self::$field['type_is_bool'] = false;
        self::$field['is_primary'] = $columnSchema['COLUMN_KEY'] === 'PRI';

        if (self::$field['type'] === ConstFieldType::VARCHAR) {
            self::$field['max_length'] = $binaryDataReader->readInt16();
        } elseif (self::$field['type'] === ConstFieldType::DOUBLE) {
            self::$field['size'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::FLOAT) {
            self::$field['size'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::TIMESTAMP2) {
            self::$field['fsp'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::DATETIME2) {
            self::$field['fsp'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::TIME2) {
            self::$field['fsp'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::TINY && $columnSchema['COLUMN_TYPE'] === 'tinyint(1)') {
            self::$field['type_is_bool'] = true;
        } elseif (self::$field['type'] === ConstFieldType::VAR_STRING || self::$field['type'] === ConstFieldType::STRING) {
            self::getFieldSpecial($binaryDataReader, $columnSchema);
        } elseif (self::$field['type'] === ConstFieldType::BLOB) {
            self::$field['length_size'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::GEOMETRY) {
            self::$field['length_size'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::JSON) {
            self::$field['length_size'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::NEWDECIMAL) {
            self::$field['precision'] = $binaryDataReader->readUInt8();
            self::$field['decimals'] = $binaryDataReader->readUInt8();
        } elseif (self::$field['type'] === ConstFieldType::BIT) {
            $bits = $binaryDataReader->readUInt8();
            $bytes = $binaryDataReader->readUInt8();
            self::$field['bits'] = ($bytes * 8) + $bits;
            self::$field['bytes'] = (int)((self::$field['bits'] + 7) / 8);
        }

        return self::$field;
    }

    /**
     * @param BinaryDataReader $packet
     * @param array $columnSchema
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    protected static function getFieldSpecial(BinaryDataReader $packet, array $columnSchema)
    {
        $metadata = ($packet->readUInt8() << 8) + $packet->readUInt8();
        $real_type = $metadata >> 8;
        if ($real_type === ConstFieldType::SET || $real_type === ConstFieldType::ENUM) {
            self::$field['type'] = $real_type;
            self::$field['size'] = $metadata & 0x00ff;
            self::getFieldSpecialValues($columnSchema);
        } else {
            self::$field['max_length'] = ((($metadata >> 4) & 0x300) ^ 0x300) + ($metadata & 0x00ff);
        }
    }

    /**
     * @param array $columnSchema
     */
    protected static function getFieldSpecialValues(array $columnSchema)
    {
        if (self::$field['type'] === ConstFieldType::ENUM) {
            self::$field['enum_values'] = explode(
                ',', str_replace(['enum(', ')', '\''], '', $columnSchema['COLUMN_TYPE'])
            );
        } else if (self::$field['type'] === ConstFieldType::SET) {
            self::$field['set_values'] = explode(
                ',', str_replace(['set(', ')', '\''], '', $columnSchema['COLUMN_TYPE'])
            );
        }
    }
}