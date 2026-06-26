<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\UpdateRowsDTO;

class UpdateRowsDTOTest extends EventDTOTestCase
{
    private UpdateRowsDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new UpdateRowsDTO(
            $this->makeEventInfo(),
            $this->makeTableMap(),
            1,
            [[
                'before' => [
                    'id' => 1,
                ],
                'after' => [
                    'id' => 2,
                ],
            ]]
        );
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::UPDATE->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame(1, $this->dto->changedRows);
        self::assertCount(1, $this->dto->values);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('test_table', $str);
    }
}
