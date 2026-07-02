<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\DeleteRowsDTO;

class DeleteRowsDTOTest extends EventDTOTestCase
{
    private DeleteRowsDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new DeleteRowsDTO($this->makeEventInfo(), $this->makeTableMap(), 1, [[
            'id' => 5,
        ]]);
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::DELETE->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame(1, $this->dto->changedRows);
        self::assertSame([[
            'id' => 5,
        ]], $this->dto->values);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('test_table', $str);
    }
}
