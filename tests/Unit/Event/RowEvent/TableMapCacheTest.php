<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use MySQLReplication\Event\RowEvent\TableMapCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class TableMapCacheTest extends TestCase
{
    private CacheInterface $cache;

    private TableMapCache $tableMapCache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->createMock(CacheInterface::class);
        $this->tableMapCache = new TableMapCache($this->cache);
    }

    public function testShouldDelegateHasToCache(): void
    {
        $this->cache->method('has')
->with('42')
->willReturn(true);
        self::assertTrue($this->tableMapCache->has('42'));
    }

    public function testShouldReturnFalseWhenNotInCache(): void
    {
        $this->cache->method('has')
->with('99')
->willReturn(false);
        self::assertFalse($this->tableMapCache->has('99'));
    }

    public function testShouldDelegateGetToCache(): void
    {
        $tableMap = new TableMap('db', 'tbl', '1', 0, new ColumnDTOCollection());
        $this->cache->method('get')
->with('1')
->willReturn($tableMap);

        self::assertSame($tableMap, $this->tableMapCache->get('1'));
    }

    public function testShouldDelegateSetToCache(): void
    {
        $tableMap = new TableMap('db', 'tbl', '5', 0, new ColumnDTOCollection());
        $this->cache->expects(self::once())->method('set')->with('5', $tableMap);

        $this->tableMapCache->set('5', $tableMap);
    }
}
