<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Repository;

use MySQLReplication\Repository\FieldDTO;
use PHPUnit\Framework\TestCase;

class FieldDTOTest extends TestCase
{
    public function testShouldMakeFromArray(): void
    {
        $data = [
            'COLUMN_NAME' => 'user_id',
            'COLLATION_NAME' => 'utf8mb4_unicode_ci',
            'CHARACTER_SET_NAME' => 'utf8mb4',
            'COLUMN_COMMENT' => 'Primary user ID',
            'COLUMN_TYPE' => 'int unsigned',
            'COLUMN_KEY' => 'PRI',
        ];

        $dto = FieldDTO::makeFromArray($data);

        self::assertSame('user_id', $dto->columnName);
        self::assertSame('utf8mb4_unicode_ci', $dto->collationName);
        self::assertSame('utf8mb4', $dto->characterSetName);
        self::assertSame('Primary user ID', $dto->columnComment);
        self::assertSame('int unsigned', $dto->columnType);
        self::assertSame('PRI', $dto->columnKey);
    }

    public function testShouldMakeFromArrayWithNullFields(): void
    {
        $data = [
            'COLUMN_NAME' => 'qty',
            'COLLATION_NAME' => null,
            'CHARACTER_SET_NAME' => null,
            'COLUMN_COMMENT' => '',
            'COLUMN_TYPE' => 'int',
            'COLUMN_KEY' => '',
        ];

        $dto = FieldDTO::makeFromArray($data);

        self::assertNull($dto->collationName);
        self::assertNull($dto->characterSetName);
    }

    public function testShouldMakeDummy(): void
    {
        $dto = FieldDTO::makeDummy(3);

        self::assertSame('DROPPED_COLUMN_3', $dto->columnName);
        self::assertNull($dto->collationName);
        self::assertNull($dto->characterSetName);
        self::assertSame('', $dto->columnComment);
        self::assertSame('BLOB', $dto->columnType);
        self::assertSame('', $dto->columnKey);
    }

    public function testShouldMakeDummyWithDifferentIndexes(): void
    {
        self::assertSame('DROPPED_COLUMN_0', FieldDTO::makeDummy(0)->columnName);
        self::assertSame('DROPPED_COLUMN_100', FieldDTO::makeDummy(100)->columnName);
    }
}
