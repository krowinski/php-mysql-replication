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

    public function testYearColumnDoesNotShiftTheSignednessOfLaterNumericColumns(): void
    {
        // MySQL 8.0 defaults to MINIMAL metadata; FULL adds the column names that make the
        // decoder use the TABLE_MAP signedness bits instead of information_schema.
        $variable = $this->connection->fetchAssociative("SHOW GLOBAL VARIABLES LIKE 'binlog_row_metadata'");
        if ($variable === false) {
            self::markTestSkipped('Server has no binlog_row_metadata');
        }
        $previous = $variable['Value'];
        $this->connection->executeStatement('SET GLOBAL binlog_row_metadata = FULL');
        try {
            $event = $this->createAndInsertValue(
                'CREATE TABLE test (y YEAR, a INT UNSIGNED, b INT, c BIGINT UNSIGNED, d TINYINT)',
                'INSERT INTO test VALUES (2020, 4294967295, -1, 18446744073709551615, -5)'
            );
        } finally {
            $this->connection->executeStatement('SET GLOBAL binlog_row_metadata = ' . $previous);
        }
        self::assertInstanceOf(WriteRowsDTO::class, $event);

        self::assertSame(2020, $event->values[0]['y']);
        self::assertSame(4294967295, $event->values[0]['a']);
        self::assertSame(-1, $event->values[0]['b']);
        self::assertSame('18446744073709551615', $event->values[0]['c']);
        self::assertSame(-5, $event->values[0]['d']);
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
