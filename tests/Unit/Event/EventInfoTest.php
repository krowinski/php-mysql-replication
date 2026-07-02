<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event;

use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\EventInfo;
use PHPUnit\Framework\TestCase;

class EventInfoTest extends TestCase
{
    public function testShouldGetTypeNameForKnownType(): void
    {
        $eventInfo = $this->makeEventInfo(type: ConstEventType::QUERY_EVENT->value);
        self::assertSame('QUERY_EVENT', $eventInfo->getTypeName());
    }

    public function testShouldGetNullTypeNameForUnknownType(): void
    {
        $eventInfo = $this->makeEventInfo(type: 9999);
        self::assertNull($eventInfo->getTypeName());
    }

    public function testShouldGetDateTimeFromTimestamp(): void
    {
        $timestamp = mktime(12, 0, 0, 1, 1, 2021);
        $eventInfo = $this->makeEventInfo(timestamp: $timestamp);
        self::assertNotNull($eventInfo->getDateTime());
        self::assertStringContainsString('2021', $eventInfo->getDateTime());
    }

    public function testShouldReturnNullDateTimeForZeroTimestamp(): void
    {
        $eventInfo = $this->makeEventInfo(timestamp: 0);
        self::assertNull($eventInfo->getDateTime());
    }

    public function testShouldGetSizeNoHeaderWithoutChecksum(): void
    {
        $eventInfo = $this->makeEventInfo(size: 100, checkSum: false);
        self::assertSame(81, $eventInfo->getSizeNoHeader());
    }

    public function testShouldGetSizeNoHeaderWithChecksum(): void
    {
        $eventInfo = $this->makeEventInfo(size: 100, checkSum: true);
        self::assertSame(77, $eventInfo->getSizeNoHeader());
    }

    public function testShouldUpdateBinLogPositionWhenPosIsPositive(): void
    {
        $binLogCurrent = new BinLogCurrent();
        $binLogCurrent->setBinFileName('binlog.001');
        $binLogCurrent->setBinLogPosition('0');

        new EventInfo(1620000000, ConstEventType::QUERY_EVENT->value, 1, 100, '500', 0, false, $binLogCurrent);

        self::assertSame('500', $binLogCurrent->getBinLogPosition());
    }

    public function testShouldNotUpdateBinLogPositionWhenPosIsZero(): void
    {
        $binLogCurrent = new BinLogCurrent();
        $binLogCurrent->setBinFileName('binlog.001');
        $binLogCurrent->setBinLogPosition('100');

        new EventInfo(1620000000, ConstEventType::QUERY_EVENT->value, 1, 100, '0', 0, false, $binLogCurrent);

        self::assertSame('100', $binLogCurrent->getBinLogPosition());
    }

    public function testShouldImplementJsonSerializable(): void
    {
        $eventInfo = $this->makeEventInfo();
        self::assertInstanceOf(\JsonSerializable::class, $eventInfo);
        $json = json_encode($eventInfo);
        self::assertIsString($json);
        $decoded = json_decode($json, true);
        self::assertArrayHasKey('timestamp', $decoded);
        self::assertArrayHasKey('type', $decoded);
    }
    private function makeEventInfo(
        int $timestamp = 1620000000,
        int $type = ConstEventType::QUERY_EVENT->value,
        int $size = 100,
        string $pos = '0',
        bool $checkSum = false
    ): EventInfo {
        $binLogCurrent = new BinLogCurrent();
        $binLogCurrent->setBinFileName('binlog.001');

        return new EventInfo($timestamp, $type, 1, $size, $pos, 0, $checkSum, $binLogCurrent);
    }
}
