<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use PHPUnit\Framework\TestCase;

class TableMapTest extends TestCase
{
    public function testShouldMakeTableMap(): void
    {
        $expected = [
            'database' => 'foo',
            'table' => 'bar',
            'tableId' => '1',
            'columnsAmount' => 2,
            'columnDTOCollection' => new ColumnDTOCollection(),
        ];

        $tableMap = new TableMap(
            $expected['database'],
            $expected['table'],
            $expected['tableId'],
            $expected['columnsAmount'],
            $expected['columnDTOCollection']
        );

        self::assertSame($expected['database'], $tableMap->database);
        self::assertSame($expected['table'], $tableMap->table);
        self::assertSame($expected['tableId'], $tableMap->tableId);
        self::assertSame($expected['columnsAmount'], $tableMap->columnsAmount);
        self::assertSame($expected['columnDTOCollection'], $tableMap->columnDTOCollection);

        self::assertInstanceOf(\JsonSerializable::class, $tableMap);
        /** @noinspection JsonEncodingApiUsageInspection */
        self::assertSame(json_encode($expected), json_encode($tableMap));
    }
}
