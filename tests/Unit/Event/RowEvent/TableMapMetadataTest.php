<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Definitions\ConstTableMapMetadataFieldType;
use MySQLReplication\Event\RowEvent\TableMapMetadata;
use PHPUnit\Framework\TestCase;

class TableMapMetadataTest extends TestCase
{
    private const RESOLVED_COLUMN_TYPES = [
        0 => ConstFieldType::LONG,
        1 => ConstFieldType::LONG,
        2 => ConstFieldType::VARCHAR,
        3 => ConstFieldType::ENUM,
        4 => ConstFieldType::SET,
    ];

    public function testShouldParseAllKnownFieldsAndSkipUnknownOnes(): void
    {
        $metadata = TableMapMetadata::parse(new BinaryDataReader($this->makeMetadataBlob()), strlen($this->makeMetadataBlob()), self::RESOLVED_COLUMN_TYPES);

        self::assertSame([
            0 => 'id',
            1 => 'amount',
            2 => 'name',
            3 => 'status',
            4 => 'flags',
        ], $metadata->columnNames);
        self::assertSame(['active', 'inactive'], $metadata->enumValues[3]);
        self::assertSame(['a', 'b', 'c'], $metadata->setValues[4]);

        self::assertFalse($metadata->isUnsigned(0));
        self::assertTrue($metadata->isUnsigned(1));
        self::assertFalse($metadata->isUnsigned(2));

        self::assertTrue($metadata->isPrimaryKey(0));
        self::assertSame(0, $metadata->primaryKeys[0]);
        self::assertTrue($metadata->isPrimaryKey(2));
        self::assertSame(10, $metadata->primaryKeys[2]);
        self::assertFalse($metadata->isPrimaryKey(1));
    }

    public function testShouldFallBackToGeneratedNameWhenMissing(): void
    {
        $metadata = TableMapMetadata::parse(new BinaryDataReader(''), 0, self::RESOLVED_COLUMN_TYPES);

        self::assertSame('COLUMN_7', $metadata->getColumnName(7, 7));
        self::assertFalse($metadata->isUnsigned(0));
        self::assertFalse($metadata->isPrimaryKey(0));
    }

    public function testShouldStopExactlyAtTotalBytesAndNotOverrunIntoTrailingData(): void
    {
        $blob = $this->makeMetadataBlob();
        $reader = new BinaryDataReader($blob . 'Z');

        TableMapMetadata::parse($reader, strlen($blob), self::RESOLVED_COLUMN_TYPES);

        self::assertSame(1, $reader->getBinaryDataLength());
        self::assertSame('Z', $reader->read(1));
    }

    private function makeMetadataBlob(): string
    {
        $field = static fn (int $type, string $payload): string => chr($type) . chr(strlen($payload)) . $payload;

        // SIGNEDNESS: 2 numeric columns (offsets 0, 1) -> bits MSB-first: col0=0 (signed), col1=1 (unsigned)
        $signedness = $field(ConstTableMapMetadataFieldType::SIGNEDNESS, chr(0b01000000));

        $columnName = static fn (string $name): string => chr(strlen($name)) . $name;
        $columnNames = $field(
            ConstTableMapMetadataFieldType::COLUMN_NAME,
            $columnName('id') . $columnName('amount') . $columnName('name') . $columnName('status') . $columnName('flags')
        );

        $enumStrValue = $field(ConstTableMapMetadataFieldType::ENUM_STR_VALUE, chr(2) . chr(6) . 'active' . chr(8) . 'inactive');
        $setStrValue = $field(ConstTableMapMetadataFieldType::SET_STR_VALUE, chr(3) . chr(1) . 'a' . chr(1) . 'b' . chr(1) . 'c');

        $simplePrimaryKey = $field(ConstTableMapMetadataFieldType::SIMPLE_PRIMARY_KEY, chr(0));
        $primaryKeyWithPrefix = $field(ConstTableMapMetadataFieldType::PRIMARY_KEY_WITH_PREFIX, chr(2) . chr(10));

        // unknown/future field type and a known-but-unused-by-us type (VISIBILITY) must be safely skippable
        $unknownField = $field(99, 'XX');
        $visibility = $field(ConstTableMapMetadataFieldType::VISIBILITY, chr(0b00011111));

        return $signedness . $columnNames . $enumStrValue . $setStrValue . $simplePrimaryKey . $primaryKeyWithPrefix . $unknownField . $visibility;
    }
}
