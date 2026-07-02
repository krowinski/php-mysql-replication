<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\XidDTO;

class XidDTOTest extends EventDTOTestCase
{
    private XidDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new XidDTO($this->makeEventInfo(), '12345');
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::XID->value, $this->dto->getType());
    }

    public function testShouldExposeXid(): void
    {
        self::assertSame('12345', $this->dto->xid);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('12345', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertSame('12345', $decoded['xid']);
    }
}
