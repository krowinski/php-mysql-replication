<?php


namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use MySQLReplication\Tests\Unit\BaseTest;

class TableMapTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakeTableMap(): void
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

        self::assertSame($expected['database'], $tableMap->getDatabase());
        self::assertSame($expected['table'], $tableMap->getTable());
        self::assertSame($expected['tableId'], $tableMap->getTableId());
        self::assertSame($expected['columnsAmount'], $tableMap->getColumnsAmount());
        self::assertSame($expected['columnDTOCollection'], $tableMap->getColumnDTOCollection());

        self::assertInstanceOf(\JsonSerializable::class, $tableMap);
        self::assertSame(json_encode($expected), json_encode($tableMap));
    }
}