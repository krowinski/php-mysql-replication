<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\RowEvent\ColumnDTO;
use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Repository\FieldDTO;
use PHPUnit\Framework\TestCase;

class ColumnDTOCollectionTest extends TestCase
{
    public function testShouldBeEmptyByDefault(): void
    {
        $collection = new ColumnDTOCollection();
        self::assertCount(0, $collection);
    }

    public function testShouldAddAndCountItems(): void
    {
        $collection = new ColumnDTOCollection();
        $fieldDTO = new FieldDTO('col', null, null, '', 'int', '');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $fieldDTO, new BinaryDataReader(''));
        $collection->add($dto);

        self::assertCount(1, $collection);
    }

    public function testShouldJsonSerialize(): void
    {
        $collection = new ColumnDTOCollection();

        $json = json_encode($collection);
        self::assertSame('[]', $json);
    }

    public function testShouldJsonSerializeWithItems(): void
    {
        $collection = new ColumnDTOCollection();
        $fieldDTO = new FieldDTO('id', null, null, '', 'int', 'PRI');
        $dto = ColumnDTO::make(ConstFieldType::LONG, $fieldDTO, new BinaryDataReader(''));
        $collection->add($dto);

        $json = json_encode($collection);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertCount(1, $decoded);
    }
}
