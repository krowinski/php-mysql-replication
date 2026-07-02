<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;

class FormatDescriptionEventDTOTest extends EventDTOTestCase
{
    private FormatDescriptionEventDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new FormatDescriptionEventDTO($this->makeEventInfo());
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::FORMAT_DESCRIPTION->value, $this->dto->getType());
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString(ConstEventsNames::FORMAT_DESCRIPTION->value, $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        self::assertIsString($json);
    }
}
