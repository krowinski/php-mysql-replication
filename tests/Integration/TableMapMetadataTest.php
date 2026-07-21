<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;

class TableMapMetadataTest extends BaseCase
{
    private const CREATE_QUERY = 'CREATE TABLE test (
        id INT UNSIGNED NOT NULL PRIMARY KEY,
        status ENUM("open", "closed", "pending"),
        tags SET("a", "b", "c"),
        name VARCHAR(20)
    )';

    private const INSERT_QUERY = 'INSERT INTO test VALUES (42, "closed", "a,c", "hello")';

    public function testShouldReadColumnNamesUnsignedEnumSetAndPrimaryKeyFromTableMapEvent(): void
    {
        $event = $this->createAndInsertValue(self::CREATE_QUERY, self::INSERT_QUERY);
        self::assertInstanceOf(WriteRowsDTO::class, $event);

        $this->assertColumnMetadataAndValues($event);
    }

    public function testShouldFallBackToInformationSchemaWhenMetadataDisabled(): void
    {
        $this->disconnect();
        $this->configBuilder->withUseTableMapMetadata(false);
        $this->connect();

        // connect() doesn't replay setUp()'s initial-event dance; reproduce it here so
        // createAndInsertValue() sees the same starting position it expects.
        if ($this->mySQLReplicationFactory?->getServerInfo()->versionRevision >= 8 && $this->mySQLReplicationFactory?->getServerInfo()->isGeneric()) {
            self::assertInstanceOf(RotateDTO::class, $this->getEvent());
        }
        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $event = $this->createAndInsertValue(self::CREATE_QUERY, self::INSERT_QUERY);
        self::assertInstanceOf(WriteRowsDTO::class, $event);

        $this->assertColumnMetadataAndValues($event);
    }

    private function assertColumnMetadataAndValues(WriteRowsDTO $event): void
    {
        $columns = $event->tableMap->columnDTOCollection;

        self::assertSame('id', $columns->offsetGet(0)->getName());
        self::assertTrue($columns->offsetGet(0)->isUnsigned());
        self::assertTrue($columns->offsetGet(0)->isPrimary());

        self::assertSame('status', $columns->offsetGet(1)->getName());
        self::assertSame(['open', 'closed', 'pending'], $columns->offsetGet(1)->getEnumValues());

        self::assertSame('tags', $columns->offsetGet(2)->getName());
        self::assertSame(['a', 'b', 'c'], $columns->offsetGet(2)->getSetValues());

        self::assertSame('name', $columns->offsetGet(3)->getName());
        self::assertFalse($columns->offsetGet(3)->isPrimary());

        self::assertSame(42, $event->values[0]['id']);
        self::assertSame('closed', $event->values[0]['status']);
        self::assertSame(['a', 'c'], $event->values[0]['tags']);
        self::assertSame('hello', $event->values[0]['name']);
    }
}
