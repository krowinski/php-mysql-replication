<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\WriteRowsDTO;

class WriteRowsDTOTest extends EventDTOTestCase
{
    private WriteRowsDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new WriteRowsDTO($this->makeEventInfo(), $this->makeTableMap(), 2, [[
            'id' => 1,
        ], [
            'id' => 2,
        ]]);
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::WRITE->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame(2, $this->dto->changedRows);
        self::assertCount(2, $this->dto->values);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('test_table', $str);
        self::assertStringContainsString('2', $str);
    }
}
