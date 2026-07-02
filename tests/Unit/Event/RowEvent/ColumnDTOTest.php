<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\RowEvent\ColumnDTO;
use MySQLReplication\Repository\FieldDTO;
use PHPUnit\Framework\TestCase;

class ColumnDTOTest extends TestCase
{
    public function testShouldMakeVarcharColumn(): void
    {
        $reader = new BinaryDataReader(pack('v', 255));
        $dto = ColumnDTO::make(ConstFieldType::VARCHAR, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::VARCHAR, $dto->type);
        self::assertSame(255, $dto->maxLength);
    }

    public function testShouldMakeBlobColumn(): void
    {
        $reader = new BinaryDataReader(pack('C', 4));
        $dto = ColumnDTO::make(ConstFieldType::BLOB, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::BLOB, $dto->type);
        self::assertSame(4, $dto->lengthSize);
    }

    public function testShouldMakeNewDecimalColumn(): void
    {
        $reader = new BinaryDataReader(pack('CC', 10, 4));
        $dto = ColumnDTO::make(ConstFieldType::NEWDECIMAL, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::NEWDECIMAL, $dto->type);
        self::assertSame(10, $dto->precision);
        self::assertSame(4, $dto->decimals);
    }

    public function testShouldMakeBitColumn(): void
    {
        $reader = new BinaryDataReader(pack('CC', 7, 1));
        $dto = ColumnDTO::make(ConstFieldType::BIT, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::BIT, $dto->type);
        self::assertSame(15, $dto->bits);
        self::assertSame(2, $dto->bytes);
    }

    public function testShouldMakeTimestamp2Column(): void
    {
        $reader = new BinaryDataReader(pack('C', 3));
        $dto = ColumnDTO::make(ConstFieldType::TIMESTAMP2, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::TIMESTAMP2, $dto->type);
        self::assertSame(3, $dto->fsp);
    }

    public function testShouldMakeDatetime2Column(): void
    {
        $reader = new BinaryDataReader(pack('C', 6));
        $dto = ColumnDTO::make(ConstFieldType::DATETIME2, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::DATETIME2, $dto->type);
        self::assertSame(6, $dto->fsp);
    }

    public function testShouldMakeFloatColumn(): void
    {
        $reader = new BinaryDataReader(pack('C', 4));
        $dto = ColumnDTO::make(ConstFieldType::FLOAT, $this->makeFieldDTO(), $reader);

        self::assertSame(ConstFieldType::FLOAT, $dto->type);
        self::assertSame(4, $dto->size);
    }

    public function testShouldGetName(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(columnName: 'user_id'), $reader);

        self::assertSame('user_id', $dto->getName());
    }

    public function testShouldGetEnumValues(): void
    {
        // first byte = ENUM type (247), second byte = size (1) → metadata = (247 << 8) + 1, realType = 247 = ENUM
        $reader = new BinaryDataReader(pack('CC', ConstFieldType::ENUM, 1));
        $fieldDTO = $this->makeFieldDTO(columnType: "enum('active','inactive')");
        $dto = ColumnDTO::make(ConstFieldType::STRING, $fieldDTO, $reader);

        self::assertSame(['active', 'inactive'], $dto->getEnumValues());
    }

    public function testShouldReturnEmptyArrayForEnumValuesOnNonEnumColumn(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(), $reader);

        self::assertSame([], $dto->getEnumValues());
    }

    public function testShouldGetSetValues(): void
    {
        // first byte = SET type (248), second byte = size (1) → metadata = (248 << 8) + 1, realType = 248 = SET
        $reader = new BinaryDataReader(pack('CC', ConstFieldType::SET, 1));
        $fieldDTO = $this->makeFieldDTO(columnType: "set('a','b','c')");
        $dto = ColumnDTO::make(ConstFieldType::STRING, $fieldDTO, $reader);

        self::assertSame(['a', 'b', 'c'], $dto->getSetValues());
    }

    public function testShouldReturnEmptyArrayForSetValuesOnNonSetColumn(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(), $reader);

        self::assertSame([], $dto->getSetValues());
    }

    public function testShouldDetectUnsignedColumn(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(columnType: 'int unsigned'), $reader);

        self::assertTrue($dto->isUnsigned());
    }

    public function testShouldDetectSignedColumn(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(columnType: 'int'), $reader);

        self::assertFalse($dto->isUnsigned());
    }

    public function testShouldDetectPrimaryKey(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(columnKey: 'PRI'), $reader);

        self::assertTrue($dto->isPrimary());
    }

    public function testShouldDetectNonPrimaryKey(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(columnKey: 'MUL'), $reader);

        self::assertFalse($dto->isPrimary());
    }

    public function testShouldJsonSerialize(): void
    {
        $reader = new BinaryDataReader('');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $this->makeFieldDTO(), $reader);

        $json = json_encode($dto);
        self::assertIsString($json);
        $decoded = json_decode($json, true);
        self::assertArrayHasKey('type', $decoded);
    }
    private function makeFieldDTO(
        string $columnType = 'int',
        string $columnKey = '',
        string $columnName = 'col'
    ): FieldDTO {
        return new FieldDTO($columnName, null, null, '', $columnType, $columnKey);
    }
}
