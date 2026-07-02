<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventSubscribers;
use PHPUnit\Framework\TestCase;

class EventSubscribersTest extends TestCase
{
    public function testShouldReturnAllSubscribedEvents(): void
    {
        $events = EventSubscribers::getSubscribedEvents();

        $expectedEventNames = [
            ConstEventsNames::TABLE_MAP->value,
            ConstEventsNames::UPDATE->value,
            ConstEventsNames::DELETE->value,
            ConstEventsNames::GTID->value,
            ConstEventsNames::QUERY->value,
            ConstEventsNames::ROTATE->value,
            ConstEventsNames::XID->value,
            ConstEventsNames::WRITE->value,
            ConstEventsNames::MARIADB_GTID->value,
            ConstEventsNames::FORMAT_DESCRIPTION->value,
            ConstEventsNames::HEARTBEAT->value,
            ConstEventsNames::ROWS_QUERY->value,
        ];

        foreach ($expectedEventNames as $eventName) {
            self::assertArrayHasKey($eventName, $events);
        }

        self::assertCount(12, $events);
    }

    public function testShouldMapEventsToCorrectHandlers(): void
    {
        $events = EventSubscribers::getSubscribedEvents();

        self::assertSame('onTableMap', $events[ConstEventsNames::TABLE_MAP->value]);
        self::assertSame('onUpdate', $events[ConstEventsNames::UPDATE->value]);
        self::assertSame('onDelete', $events[ConstEventsNames::DELETE->value]);
        self::assertSame('onGTID', $events[ConstEventsNames::GTID->value]);
        self::assertSame('onQuery', $events[ConstEventsNames::QUERY->value]);
        self::assertSame('onRotate', $events[ConstEventsNames::ROTATE->value]);
        self::assertSame('onXID', $events[ConstEventsNames::XID->value]);
        self::assertSame('onWrite', $events[ConstEventsNames::WRITE->value]);
        self::assertSame('onMariaDbGtid', $events[ConstEventsNames::MARIADB_GTID->value]);
        self::assertSame('onFormatDescription', $events[ConstEventsNames::FORMAT_DESCRIPTION->value]);
        self::assertSame('onHeartbeat', $events[ConstEventsNames::HEARTBEAT->value]);
        self::assertSame('onRowsQuery', $events[ConstEventsNames::ROWS_QUERY->value]);
    }
}
