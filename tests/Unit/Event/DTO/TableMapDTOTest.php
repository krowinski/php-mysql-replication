<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\TableMapDTO;

class TableMapDTOTest extends EventDTOTestCase
{
    private TableMapDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new TableMapDTO($this->makeEventInfo(), $this->makeTableMap());
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::TABLE_MAP->value, $this->dto->getType());
    }

    public function testShouldExposeTableMap(): void
    {
        self::assertSame('test_db', $this->dto->tableMap->database);
        self::assertSame('test_table', $this->dto->tableMap->table);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('test_table', $str);
        self::assertStringContainsString('test_db', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        self::assertIsString($json);
        $decoded = json_decode($json, true);
        self::assertArrayHasKey('tableMap', $decoded);
    }
}
