<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\RotateDTO;

class RotateDTOTest extends EventDTOTestCase
{
    private RotateDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new RotateDTO($this->makeEventInfo(), '4', 'mysql-bin.000002');
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::ROTATE->value, $this->dto->getType());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame('4', $this->dto->position);
        self::assertSame('mysql-bin.000002', $this->dto->nextBinlog);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('mysql-bin.000002', $str);
        self::assertStringContainsString('4', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertSame('4', $decoded['position']);
        self::assertSame('mysql-bin.000002', $decoded['nextBinlog']);
    }
}
