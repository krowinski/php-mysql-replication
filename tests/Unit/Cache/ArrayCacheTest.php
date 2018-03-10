<?php

namespace BinaryDataReader\Unit;

use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class ArrayCache
 * @package MySQLReplication\Cache
 */
class ArrayCacheTest extends BaseTest
{
    /**
     * @var ArrayCache
     */
    private $arrayCache;

    public function setUp()
    {
        parent::setUp();
        $this->arrayCache = new ArrayCache();
    }

    /**
     * @test
     */
    public function shouldGet()
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldSet()
    {
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldClearCacheOnSet()
    {
        (new ConfigBuilder())->withTableCacheSize(1)->build();

        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo', 'bar');
        self::assertSame('bar', $this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldDelete()
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->delete('foo');
        self::assertNull($this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldClear()
    {
        $this->arrayCache->set('foo', 'bar');
        $this->arrayCache->set('foo1', 'bar1');
        $this->arrayCache->clear();
        self::assertNull($this->arrayCache->get('foo'));
    }

    /**
     * @test
     */
    public function shouldGetMultiple()
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1'];
        $this->arrayCache->setMultiple($expect);
        self::assertSame(['foo' => 'bar'], $this->arrayCache->getMultiple(['foo']));
    }

    /**
     * @test
     */
    public function shouldSetMultiple()
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1'];
        $this->arrayCache->setMultiple($expect);
        self::assertSame($expect, $this->arrayCache->getMultiple(['foo', 'foo1']));
    }

    /**
     * @test
     */
    public function shouldDeleteMultiple()
    {
        $expect = ['foo' => 'bar', 'foo1' => 'bar1', 'foo2' => 'bar2'];
        $this->arrayCache->setMultiple($expect);
        $this->arrayCache->deleteMultiple(['foo', 'foo1']);
        self::assertSame(['foo2' => 'bar2'], $this->arrayCache->getMultiple(['foo2']));
    }

    /**
     * @test
     */
    public function shouldHas()
    {
        self::assertFalse($this->arrayCache->has('foo'));
        $this->arrayCache->set('foo', 'bar');
        self::assertTrue($this->arrayCache->has('foo'));
    }
}