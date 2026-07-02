<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\HeartbeatDTO;

class HeartbeatDTOTest extends EventDTOTestCase
{
    private HeartbeatDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new HeartbeatDTO($this->makeEventInfo());
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::HEARTBEAT->value, $this->dto->getType());
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString(ConstEventsNames::HEARTBEAT->value, $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        self::assertIsString($json);
    }
}
