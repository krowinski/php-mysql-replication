<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\BinLog;

use MySQLReplication\BinLog\BinLogCurrent;
use PHPUnit\Framework\TestCase;

class BinLogCurrentTest extends TestCase
{
    private BinLogCurrent $binLogCurrent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->binLogCurrent = new BinLogCurrent();
    }

    public function testShouldSetAndGetBinLogPosition(): void
    {
        $this->binLogCurrent->setBinLogPosition('12345');
        self::assertSame('12345', $this->binLogCurrent->getBinLogPosition());
    }

    public function testShouldSetAndGetBinFileName(): void
    {
        $this->binLogCurrent->setBinFileName('mysql-bin.000001');
        self::assertSame('mysql-bin.000001', $this->binLogCurrent->getBinFileName());
    }

    public function testShouldSetAndGetGtid(): void
    {
        $this->binLogCurrent->setGtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1');
        self::assertSame('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1', $this->binLogCurrent->getGtid());
    }

    public function testShouldSetAndGetMariaDbGtid(): void
    {
        $this->binLogCurrent->setMariaDbGtid('1-1-1');
        self::assertSame('1-1-1', $this->binLogCurrent->getMariaDbGtid());
    }

    public function testShouldJsonSerialize(): void
    {
        $this->binLogCurrent->setBinLogPosition('100');
        $this->binLogCurrent->setBinFileName('binlog.001');
        $this->binLogCurrent->setGtid('abc:1');
        $this->binLogCurrent->setMariaDbGtid('1-1-100');

        $json = json_encode($this->binLogCurrent);
        $decoded = json_decode($json, true);

        self::assertSame('100', $decoded['binLogPosition']);
        self::assertSame('binlog.001', $decoded['binFileName']);
        self::assertSame('abc:1', $decoded['gtid']);
        self::assertSame('1-1-100', $decoded['mariaDbGtid']);
    }

    public function testShouldImplementJsonSerializable(): void
    {
        self::assertInstanceOf(\JsonSerializable::class, $this->binLogCurrent);
    }
}
