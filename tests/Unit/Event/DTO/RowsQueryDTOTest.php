<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\RowsQueryDTO;

class RowsQueryDTOTest extends EventDTOTestCase
{
    private RowsQueryDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new RowsQueryDTO($this->makeEventInfo(), 'INSERT INTO foo VALUES (1)');
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::ROWS_QUERY->value, $this->dto->getType());
    }

    public function testShouldExposeQuery(): void
    {
        self::assertSame('INSERT INTO foo VALUES (1)', $this->dto->query);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('INSERT INTO foo VALUES (1)', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertSame('INSERT INTO foo VALUES (1)', $decoded['query']);
    }
}
