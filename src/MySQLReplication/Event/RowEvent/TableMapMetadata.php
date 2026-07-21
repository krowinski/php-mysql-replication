<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Definitions\ConstTableMapMetadataFieldType;

/**
 * Parses the optional metadata fields block of a TABLE_MAP_EVENT, written when
 * binlog_row_metadata is set to FULL.
 *
 * @see https://dev.mysql.com/blog-archive/more-metadata-is-written-into-binary-log/
 */
readonly class TableMapMetadata
{
    private const NUMERIC_TYPES = [
        ConstFieldType::TINY,
        ConstFieldType::SHORT,
        ConstFieldType::INT24,
        ConstFieldType::LONG,
        ConstFieldType::LONGLONG,
        ConstFieldType::NEWDECIMAL,
        ConstFieldType::FLOAT,
        ConstFieldType::DOUBLE,
    ];

    public function __construct(
        public array $columnNames,
        public array $unsignedColumns,
        public array $enumValues,
        public array $setValues,
        public array $primaryKeys
    ) {
    }

    /**
     * @param array<int, int> $resolvedColumnTypes column offset => resolved ConstFieldType (ENUM/SET already resolved)
     */
    public static function parse(BinaryDataReader $binaryDataReader, int $totalBytes, array $resolvedColumnTypes): self
    {
        $numericOffsets = [];
        $enumOffsets = [];
        $setOffsets = [];
        foreach ($resolvedColumnTypes as $offset => $type) {
            if (in_array($type, self::NUMERIC_TYPES, true)) {
                $numericOffsets[] = $offset;
            } elseif ($type === ConstFieldType::ENUM) {
                $enumOffsets[] = $offset;
            } elseif ($type === ConstFieldType::SET) {
                $setOffsets[] = $offset;
            }
        }

        $columnNames = [];
        $unsignedColumns = [];
        $enumValues = [];
        $setValues = [];
        $primaryKeys = [];

        $startReadBytes = $binaryDataReader->getReadBytes();
        while ($binaryDataReader->getReadBytes() - $startReadBytes < $totalBytes) {
            $fieldType = $binaryDataReader->readUInt8();
            $fieldLength = (int)$binaryDataReader->readCodedBinary();
            $fieldReader = new BinaryDataReader($binaryDataReader->read($fieldLength));

            match ($fieldType) {
                ConstTableMapMetadataFieldType::SIGNEDNESS => self::parseSignedness($fieldReader, $numericOffsets, $unsignedColumns),
                ConstTableMapMetadataFieldType::COLUMN_NAME => self::parseColumnNames($fieldReader, $columnNames),
                ConstTableMapMetadataFieldType::ENUM_STR_VALUE => self::parseStrValues($fieldReader, $enumOffsets, $enumValues),
                ConstTableMapMetadataFieldType::SET_STR_VALUE => self::parseStrValues($fieldReader, $setOffsets, $setValues),
                ConstTableMapMetadataFieldType::SIMPLE_PRIMARY_KEY => self::parseSimplePrimaryKey($fieldReader, $primaryKeys),
                ConstTableMapMetadataFieldType::PRIMARY_KEY_WITH_PREFIX => self::parsePrimaryKeyWithPrefix($fieldReader, $primaryKeys),
                // DEFAULT_CHARSET, COLUMN_CHARSET, GEOMETRY_TYPE, ENUM_AND_SET_*_CHARSET, VISIBILITY and any
                // unknown future field type: already consumed above via the length-prefixed read(), safe to skip.
                default => null,
            };
        }

        return new self($columnNames, $unsignedColumns, $enumValues, $setValues, $primaryKeys);
    }

    public function getColumnName(int $offset, int $fallback): string
    {
        return $this->columnNames[$offset] ?? ('COLUMN_' . $fallback);
    }

    public function isUnsigned(int $offset): bool
    {
        return $this->unsignedColumns[$offset] ?? false;
    }

    public function isPrimaryKey(int $offset): bool
    {
        return array_key_exists($offset, $this->primaryKeys);
    }

    /**
     * @param array<int, int> $numericOffsets
     * @param array<int, bool> $unsignedColumns
     */
    private static function parseSignedness(BinaryDataReader $fieldReader, array $numericOffsets, array &$unsignedColumns): void
    {
        $bytes = $fieldReader->read($fieldReader->getBinaryDataLength());
        foreach ($numericOffsets as $i => $offset) {
            $byte = ord($bytes[intdiv($i, 8)] ?? "\0");
            $unsignedColumns[$offset] = (($byte >> (7 - ($i % 8))) & 1) === 1;
        }
    }

    /**
     * @param array<int, string> $columnNames
     */
    private static function parseColumnNames(BinaryDataReader $fieldReader, array &$columnNames): void
    {
        $offset = 0;
        while ($fieldReader->getBinaryDataLength() > 0) {
            $length = (int)$fieldReader->readCodedBinary();
            $columnNames[$offset] = $fieldReader->read($length);
            ++$offset;
        }
    }

    /**
     * @param array<int, int> $offsets
     * @param array<int, array<int, string>> $target
     */
    private static function parseStrValues(BinaryDataReader $fieldReader, array $offsets, array &$target): void
    {
        foreach ($offsets as $offset) {
            $count = (int)$fieldReader->readCodedBinary();
            $values = [];
            for ($i = 0; $i < $count; ++$i) {
                $length = (int)$fieldReader->readCodedBinary();
                $values[] = $fieldReader->read($length);
            }
            $target[$offset] = $values;
        }
    }

    /**
     * @param array<int, int> $primaryKeys
     */
    private static function parseSimplePrimaryKey(BinaryDataReader $fieldReader, array &$primaryKeys): void
    {
        while ($fieldReader->getBinaryDataLength() > 0) {
            $offset = (int)$fieldReader->readCodedBinary();
            $primaryKeys[$offset] = 0;
        }
    }

    /**
     * @param array<int, int> $primaryKeys
     */
    private static function parsePrimaryKeyWithPrefix(BinaryDataReader $fieldReader, array &$primaryKeys): void
    {
        while ($fieldReader->getBinaryDataLength() > 0) {
            $offset = (int)$fieldReader->readCodedBinary();
            $prefixLength = (int)$fieldReader->readCodedBinary();
            $primaryKeys[$offset] = $prefixLength;
        }
    }
}
