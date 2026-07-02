<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Repository;

use MySQLReplication\Repository\FieldDTO;
use MySQLReplication\Repository\FieldDTOCollection;
use PHPUnit\Framework\TestCase;

class FieldDTOCollectionTest extends TestCase
{
    public function testShouldMakeFromEmptyArray(): void
    {
        $collection = FieldDTOCollection::makeFromArray([]);
        self::assertCount(0, $collection);
    }

    public function testShouldMakeFromArrayWithItems(): void
    {
        $fields = [
            [
                'COLUMN_NAME' => 'id',
                'COLLATION_NAME' => null,
                'CHARACTER_SET_NAME' => null,
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'int',
                'COLUMN_KEY' => 'PRI',
            ],
            [
                'COLUMN_NAME' => 'name',
                'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                'CHARACTER_SET_NAME' => 'utf8mb4',
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'varchar(255)',
                'COLUMN_KEY' => '',
            ],
        ];

        $collection = FieldDTOCollection::makeFromArray($fields);

        self::assertCount(2, $collection);
        self::assertInstanceOf(FieldDTO::class, $collection->first());
        self::assertSame('id', $collection->first()->columnName);
    }

    public function testShouldContainFieldDTOInstances(): void
    {
        $fields = [
            [
                'COLUMN_NAME' => 'status',
                'COLLATION_NAME' => null,
                'CHARACTER_SET_NAME' => null,
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'tinyint',
                'COLUMN_KEY' => '',
            ],
        ];

        $collection = FieldDTOCollection::makeFromArray($fields);

        foreach ($collection as $item) {
            self::assertInstanceOf(FieldDTO::class, $item);
        }
    }
}
