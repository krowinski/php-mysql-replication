<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\GTIDLogDTO;

class GTIDLogDTOTest extends EventDTOTestCase
{
    private GTIDLogDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new GTIDLogDTO($this->makeEventInfo(), true, '9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1');
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::GTID->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertTrue($this->dto->commit);
        self::assertSame('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1', $this->dto->gtid);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1', $str);
        self::assertStringContainsString('true', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertTrue($decoded['commit']);
        self::assertSame('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1', $decoded['gtid']);
    }
}
