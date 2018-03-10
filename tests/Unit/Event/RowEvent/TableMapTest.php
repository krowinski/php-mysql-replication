<?php


namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\Event\RowEvent\TableMap;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class RowEventTest
 * @package MySQLReplication\Tests\Unit\Event\RowEvent
 */
class TableMapTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakeTableMap()
    {
        $expected = [
            'database'      => 'foo',
            'table'         => 'bar',
            'tableId'       => 1,
            'columnsAmount' => 2,
            'fields'        => ['foo1' => 'bar1'],
        ];


        $tableMap = new TableMap(
            $expected['database'],
            $expected['table'],
            $expected['tableId'],
            $expected['columnsAmount'],
            $expected['fields']
        );

        self::assertSame($expected['database'], $tableMap->getDatabase());
        self::assertSame($expected['table'], $tableMap->getTable());
        self::assertSame($expected['tableId'], $tableMap->getTableId());
        self::assertSame($expected['columnsAmount'], $tableMap->getColumnsAmount());
        self::assertSame($expected['fields'], $tableMap->getFields());

        self::assertInstanceOf(\JsonSerializable::class, $tableMap);
        self::assertSame(json_encode($expected), json_encode($tableMap));
    }
}