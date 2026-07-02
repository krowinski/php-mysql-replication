<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\MariaDbGtidLogDTO;

class MariaDbGtidLogDTOTest extends EventDTOTestCase
{
    private MariaDbGtidLogDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new MariaDbGtidLogDTO($this->makeEventInfo(), 1, 0, '0-1-100');
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::MARIADB_GTID->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame(1, $this->dto->flag);
        self::assertSame(0, $this->dto->domainId);
        self::assertSame('0-1-100', $this->dto->mariaDbGtid);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('0-1-100', $str);
        self::assertStringContainsString('0', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertSame('0-1-100', $decoded['mariaDbGtid']);
        self::assertSame(0, $decoded['domainId']);
    }
}
