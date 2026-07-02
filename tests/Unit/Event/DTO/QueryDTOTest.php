<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\QueryDTO;

class QueryDTOTest extends EventDTOTestCase
{
    private QueryDTO $dto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dto = new QueryDTO($this->makeEventInfo(), 'mydb', 5, 'SELECT 1', 42);
    }

    public function testShouldGetType(): void
    {
        self::assertSame(ConstEventsNames::QUERY->value, $this->dto->getType());
    }

    public function testShouldGetEventInfo(): void
    {
        self::assertNotNull($this->dto->getEventInfo());
    }

    public function testShouldExposeProperties(): void
    {
        self::assertSame('mydb', $this->dto->database);
        self::assertSame(5, $this->dto->executionTime);
        self::assertSame('SELECT 1', $this->dto->query);
        self::assertSame(42, $this->dto->threadId);
    }

    public function testShouldCastToString(): void
    {
        $str = (string)$this->dto;
        self::assertStringContainsString('mydb', $str);
        self::assertStringContainsString('SELECT 1', $str);
        self::assertStringContainsString('42', $str);
    }

    public function testShouldJsonSerialize(): void
    {
        $json = json_encode($this->dto);
        $decoded = json_decode($json, true);
        self::assertSame('mydb', $decoded['database']);
        self::assertSame('SELECT 1', $decoded['query']);
    }
}
