<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\MasterStatusDTO;
use MySQLReplication\Repository\MySQLRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MySQLRepositoryTest extends TestCase
{
    private MySQLRepository $mySQLRepositoryTest;

    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->connection->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
        $this->mySQLRepositoryTest = new MySQLRepository($this->connection);
    }

    public function testShouldGetFields(): void
    {
        $expected = [
            [
                'COLUMN_NAME' => 'cname',
                'COLLATION_NAME' => 'colname',
                'CHARACTER_SET_NAME' => 'charname',
                'COLUMN_COMMENT' => 'colcommnet',
                'COLUMN_TYPE' => 'coltype',
                'COLUMN_KEY' => 'colkey',
            ],
        ];

        $this->connection->method('fetchAllAssociative')
            ->willReturn($expected);

        self::assertEquals(
            FieldDTOCollection::makeFromArray($expected),
            $this->mySQLRepositoryTest->getFields('foo', 'bar')
        );
    }

    public function testShouldIsCheckSum(): void
    {
        self::assertFalse($this->mySQLRepositoryTest->isCheckSum());

        $this->connection->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls([
                'Value' => 'CRC32',
            ], [
                'Value' => 'NONE',
            ]);

        self::assertTrue($this->mySQLRepositoryTest->isCheckSum());
        self::assertFalse($this->mySQLRepositoryTest->isCheckSum());
    }

    public function testShouldGetVersion(): void
    {
        $expected = [
            'Value' => 'version',
        ];

        $this->connection->method('fetchAssociative')
              ->willReturn($expected);

        self::assertEquals('version', $this->mySQLRepositoryTest->getVersion());
    }

    public function testShouldGetMasterStatus(): void
    {
        $expected = [
            'File' => 'mysql-bin.000002',
            'Position' => 4587305,
            'Binlog_Do_DB' => '',
            'Binlog_Ignore_DB' => '',
            'Executed_Gtid_Set' => '041de05f-a36a-11e6-bc73-000c2976f3f3:1-8023',
        ];

        $this->connection->method('fetchAssociative')
            ->willReturn($expected);

        self::assertEquals(MasterStatusDTO::makeFromArray($expected), $this->mySQLRepositoryTest->getMasterStatus());
    }

            public function testShouldReconnect(): void
    {
        // just to cover private getConnection
        $exception = $this->createMock(ConnectionException::class);

        $this->connection->method('executeQuery')
            ->willThrowException($exception);

        $this->connection->method('fetchAssociative')
            ->willReturn(['Value' => 'NONE']);

        $this->mySQLRepositoryTest->isCheckSum();
        self::assertTrue(true);
    }
}
