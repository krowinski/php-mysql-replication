<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Repository;

use MySQLReplication\Repository\MasterStatusDTO;
use PHPUnit\Framework\TestCase;

class MasterStatusDTOTest extends TestCase
{
    public function testShouldMakeFromArray(): void
    {
        $data = [
            'Position' => 4587305,
            'File' => 'mysql-bin.000002',
        ];

        $dto = MasterStatusDTO::makeFromArray($data);

        self::assertSame('4587305', $dto->position);
        self::assertSame('mysql-bin.000002', $dto->file);
    }

    public function testShouldCastPositionToString(): void
    {
        $dto = MasterStatusDTO::makeFromArray([
            'Position' => 0,
            'File' => 'binlog.001',
        ]);
        self::assertSame('0', $dto->position);
        self::assertIsString($dto->position);
    }

    public function testShouldMakeDirectly(): void
    {
        $dto = new MasterStatusDTO('100', 'mysql-bin.000001');
        self::assertSame('100', $dto->position);
        self::assertSame('mysql-bin.000001', $dto->file);
    }
}
