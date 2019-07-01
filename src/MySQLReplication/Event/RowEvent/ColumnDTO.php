<?php
declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Repository\FieldDTO;

class ColumnDTO
{
    private $fieldDTO;
    private $maxLength;
    private $size;
    private $fsp;
    private $lengthSize;
    private $precision;
    private $decimals;
    private $bits;
    private $bytes;
    private $type;

    public function __construct(
        FieldDTO $fieldDTO,
        int $type,
        int $maxLength,
        int $size,
        int $fsp,
        int $lengthSize,
        int $precision,
        int $decimals,
        int $bits,
        int $bytes
    ) {
        $this->fieldDTO = $fieldDTO;
        $this->type = $type;
        $this->maxLength = $maxLength;
        $this->size = $size;
        $this->fsp = $fsp;
        $this->lengthSize = $lengthSize;
        $this->precision = $precision;
        $this->decimals = $decimals;
        $this->bits = $bits;
        $this->bytes = $bytes;
    }

    public static function make(
        int $columnType,
        FieldDTO $fieldDTO,
        BinaryDataReader $binaryDataReader
    ): self {
        $maxLength = 0;
        $size = 0;
        $fsp = 0;
        $lengthSize = 0;
        $precision = 0;
        $decimals = 0;
        $bits = 0;
        $bytes = 0;

        if ($columnType === ConstFieldType::VARCHAR) {
            $maxLength = $binaryDataReader->readInt16();
        } else if ($columnType === ConstFieldType::DOUBLE) {
            $size = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::FLOAT) {
            $size = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::TIMESTAMP2) {
            $fsp = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::DATETIME2) {
            $fsp = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::TIME2) {
            $fsp = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::VAR_STRING || $columnType === ConstFieldType::STRING) {
            $metadata = ($binaryDataReader->readUInt8() << 8) + $binaryDataReader->readUInt8();
            $realType = $metadata >> 8;
            if ($realType === ConstFieldType::SET || $realType === ConstFieldType::ENUM) {
                $columnType = $realType;
                $size = $metadata & 0x00ff;
            } else {
                $maxLength = ((($metadata >> 4) & 0x300) ^ 0x300) + ($metadata & 0x00ff);
            }
        } else if ($columnType === ConstFieldType::BLOB || $columnType === ConstFieldType::IGNORE) {
            $lengthSize = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::GEOMETRY) {
            $lengthSize = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::JSON) {
            $lengthSize = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::NEWDECIMAL) {
            $precision = $binaryDataReader->readUInt8();
            $decimals = $binaryDataReader->readUInt8();
        } else if ($columnType === ConstFieldType::BIT) {
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

    public function getFieldDTO(): FieldDTO
    {
        return $this->fieldDTO;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getFsp(): int
    {
        return $this->fsp;
    }

    public function getLengthSize(): int
    {
        return $this->lengthSize;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function getBits(): int
    {
        return $this->bits;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->fieldDTO->getColumnName();
    }

    public function getEnumValues(): array
    {
        if ($this->type === ConstFieldType::ENUM) {
            return explode(',', str_replace(['enum(', ')', '\''], '', $this->fieldDTO->getColumnType()));
        }

        return [];
    }

    public function getSetValues(): array
    {
        if ($this->type === ConstFieldType::SET) {
            return explode(',', str_replace(['set(', ')', '\''], '', $this->fieldDTO->getColumnType()));
        }

        return [];
    }

    public function isUnsigned(): bool
    {
        return !(stripos($this->fieldDTO->getColumnType(), 'unsigned') === false);
    }

    public function isPrimary(): bool
    {
        return $this->fieldDTO->getColumnKey() === 'PRI';
    }
}