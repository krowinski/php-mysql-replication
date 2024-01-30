<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use JsonSerializable;
use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Repository\FieldDTO;

readonly class ColumnDTO implements JsonSerializable
{
    public function __construct(
        public FieldDTO $fieldDTO,
        public int $type,
        public int $maxLength,
        public int $size,
        public int $fsp,
        public int $lengthSize,
        public int $precision,
        public int $decimals,
        public int $bits,
        public int $bytes
    ) {
    }

    public static function make(int $columnType, FieldDTO $fieldDTO, BinaryDataReader $binaryDataReader): self
    {
        $maxLength = 0;
        $size = 0;
        $fsp = 0;
        $lengthSize = 0;
        $precision = 0;
        $decimals = 0;
        $bits = 0;
        $bytes = 0;

        if ($columnType === ConstFieldType::VARCHAR) {
            $maxLength = $binaryDataReader->readUInt16();
        } elseif ($columnType === ConstFieldType::DOUBLE) {
            $size = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::FLOAT) {
            $size = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::TIMESTAMP2) {
            $fsp = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::DATETIME2) {
            $fsp = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::TIME2) {
            $fsp = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::VAR_STRING || $columnType === ConstFieldType::STRING) {
            $metadata = ($binaryDataReader->readUInt8() << 8) + $binaryDataReader->readUInt8();
            $realType = $metadata >> 8;
            if ($realType === ConstFieldType::SET || $realType === ConstFieldType::ENUM) {
                $columnType = $realType;
                $size = $metadata & 0x00ff;
            } else {
                $maxLength = ((($metadata >> 4) & 0x300) ^ 0x300) + ($metadata & 0x00ff);
            }
        } elseif ($columnType === ConstFieldType::BLOB || $columnType === ConstFieldType::IGNORE) {
            $lengthSize = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::GEOMETRY) {
            $lengthSize = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::JSON) {
            $lengthSize = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::NEWDECIMAL) {
            $precision = $binaryDataReader->readUInt8();
            $decimals = $binaryDataReader->readUInt8();
        } elseif ($columnType === ConstFieldType::BIT) {
            $bits = $binaryDataReader->readUInt8();
            $bytes = $binaryDataReader->readUInt8();

            $bits = ($bytes * 8) + $bits;
            $bytes = (int)(($bits + 7) / 8);
        }

        return new self(
            $fieldDTO,
            $columnType,
            $maxLength,
            $size,
            $fsp,
            $lengthSize,
            $precision,
            $decimals,
            $bits,
            $bytes
        );
    }

    public function getName(): string
    {
        return $this->fieldDTO->columnName;
    }

    public function getEnumValues(): array
    {
        if ($this->type === ConstFieldType::ENUM) {
            return explode(',', str_replace(['enum(', ')', '\''], '', $this->fieldDTO->columnType));
        }

        return [];
    }

    public function getSetValues(): array
    {
        if ($this->type === ConstFieldType::SET) {
            return explode(',', str_replace(['set(', ')', '\''], '', $this->fieldDTO->columnType));
        }

        return [];
    }

    public function isUnsigned(): bool
    {
        return !(stripos($this->fieldDTO->columnType, 'unsigned') === false);
    }

    public function isPrimary(): bool
    {
        return $this->fieldDTO->columnKey === 'PRI';
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
