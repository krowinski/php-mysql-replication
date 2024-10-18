<?php
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Cache;

use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Tests\Unit\BaseTest;

class ArrayCacheTest extends BaseTest
{
    private $arrayCache;

    public function setUp(): void
    {
        parent::setUp();
        $this->arrayCache = new ArrayCache();
    }

    /**
     * @test
     */
    public function shouldGet(): void
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldSet(): void
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldClearCacheOnSet(): void
    {
        (new ConfigBuilder())->withTableCacheSize(1)->build();

        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldDelete(): void
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->delete('foo');
        self::assertNull($this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldClear(): void
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo1', 'bar1');
        $this->arrayCache->clear();
        self::assertNull($this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldGetMultiple(): void
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1'];
        $this->arrayCache->setMultiple($expect);
        self::assertSame(['foo' => 'bar'], $this->arrayCache->getMultiple(['foo']));
    }

    /**
     * @test
     */
    public function shouldSetMultiple(): void
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1'];
        $this->arrayCache->setMultiple($expect);
        self::assertSame($expect, $this->arrayCache->getMultiple(['foo', 'foo1']));
    }

    /**
     * @test
     */
    public function shouldDeleteMultiple(): void
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1', 'foo2' => 'bar2'];
        $this->arrayCache->setMultiple($expect);
        $this->arrayCache->deleteMultiple(['foo', 'foo1']);
        self::assertSame(['foo2' => 'bar2'], $this->arrayCache->getMultiple(['foo2']));
    }

    /**
     * @test
     */
    public function shouldHas(): void
    {
        self::assertFalse($this->arrayCache->has('foo'));
        $this->arrayCache->set('foo', 'bar');
        self::assertTrue($this->arrayCache->has('foo'));
    }
}
