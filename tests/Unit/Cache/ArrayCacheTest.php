<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Cache;

use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\ConfigBuilder;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    private $arrayCache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arrayCache = new ArrayCache();
    }

    public function testShouldGet(): void
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    public function testShouldSet(): void
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    public function testShouldClearCacheOnSet(): void
    {
        (new ConfigBuilder())->withTableCacheSize(1)
            ->build();

        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    public function testShouldDelete(): void
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->delete('foo');
        self::assertNull($this->arrayCache->get('foo'));
    }

    public function testShouldClear(): void
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo1', 'bar1');
        $this->arrayCache->clear();
        self::assertNull($this->arrayCache->get('foo'));
    }

    public function testShouldGetMultiple(): void
    {
        $expect = [
            'foo' => 'bar',
            'foo1' => 'bar1',
        ];
        $this->arrayCache->setMultiple($expect);
        self::assertSame([
            'foo' => 'bar',
        ], $this->arrayCache->getMultiple(['foo']));
    }

    public function testShouldSetMultiple(): void
    {
        $expect = [
            'foo' => 'bar',
            'foo1' => 'bar1',
        ];
        $this->arrayCache->setMultiple($expect);
        self::assertSame($expect, $this->arrayCache->getMultiple(['foo', 'foo1']));
    }

    public function testShouldDeleteMultiple(): void
    {
        $expect = [
            'foo' => 'bar',
            'foo1' => 'bar1',
            'foo2' => 'bar2',
        ];
        $this->arrayCache->setMultiple($expect);
        $this->arrayCache->deleteMultiple(['foo', 'foo1']);
        self::assertSame([
            'foo2' => 'bar2',
        ], $this->arrayCache->getMultiple(['foo2']));
    }

    public function testShouldHas(): void
    {
        self::assertFalse($this->arrayCache->has('foo'));
        $this->arrayCache->set('foo', 'bar');
        self::assertTrue($this->arrayCache->has('foo'));
    }
}
