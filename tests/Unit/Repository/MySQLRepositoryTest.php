<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\MasterStatusDTO;
use MySQLReplication\Repository\MySQLRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MySQLRepositoryTest extends TestCase
{
    private MySQLRepository $mySQLRepositoryTest;

    private Connection&Stub $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createStub(Connection::class);
        $this->connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
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

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(self::anything(), ['foo', 'bar'])
            ->willReturn($expected);

        self::assertEquals(
            FieldDTOCollection::makeFromArray($expected),
            (new MySQLRepository($connection))->getFields('foo', 'bar')
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

    public function testShouldIsRowFormat(): void
    {
        $this->connection->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls([
                'Value' => 'ROW',
            ], [
                'Value' => 'STATEMENT',
            ]);

        self::assertTrue($this->mySQLRepositoryTest->isRowFormat());
        self::assertFalse($this->mySQLRepositoryTest->isRowFormat());
    }

    public function testShouldIsRowImageFull(): void
    {
        $this->connection->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls([
                'Value' => 'FULL',
            ], [
                'Value' => 'MINIMAL',
            ], false);

        self::assertTrue($this->mySQLRepositoryTest->isRowImageFull());
        self::assertFalse($this->mySQLRepositoryTest->isRowImageFull());
        // versions without the variable have no partial row image mode
        self::assertTrue($this->mySQLRepositoryTest->isRowImageFull());
    }

    public function testShouldGetVersion(): void
    {
        $expected = [
            'Value' => 'version',
        ];

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($expected);

        $repository = new MySQLRepository($connection);

        self::assertEquals('version', $repository->getVersion());
        // second call must hit the cache, not the connection again
        self::assertEquals('version', $repository->getVersion());
    }

    public function testShouldGetGtidExecutedForMysql(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    'Value' => '8.0.46',
                ],
                [
                    'Gtid_Executed' => '041de05f-a36a-11e6-bc73-000c2976f3f3:1-8023',
                ]
            );

        $repository = new MySQLRepository($connection);

        self::assertEquals('041de05f-a36a-11e6-bc73-000c2976f3f3:1-8023', $repository->getGtidExecuted());
    }

    public function testShouldGetGtidExecutedForMariaDb(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls([
                'Value' => '10.11.6-MariaDB',
            ], [
                'Gtid_Executed' => '0-1-100',
            ]);

        $repository = new MySQLRepository($connection);

        self::assertEquals('0-1-100', $repository->getGtidExecuted());
    }

    public function testShouldIsSemiSyncEnabledForMysql(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'Variable_name' => 'rpl_semi_sync_source_enabled',
                    'Value' => 'ON',
                ],
            ]);

        $repository = new MySQLRepository($connection);

        self::assertTrue($repository->isSemiSyncEnabled());
    }

    public function testShouldIsSemiSyncEnabledForOldMysql(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'Variable_name' => 'rpl_semi_sync_master_enabled',
                    'Value' => 'ON',
                ],
            ]);

        $repository = new MySQLRepository($connection);

        self::assertTrue($repository->isSemiSyncEnabled());
    }

    public function testShouldIsSemiSyncEnabledFalseWhenNotConfigured(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $repository = new MySQLRepository($connection);

        self::assertFalse($repository->isSemiSyncEnabled());
    }

    public function testShouldIsSemiSyncEnabledFalseWhenOff(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'Variable_name' => 'rpl_semi_sync_source_enabled',
                    'Value' => 'OFF',
                ],
            ]);

        $repository = new MySQLRepository($connection);

        self::assertFalse($repository->isSemiSyncEnabled());
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function masterStatusQueryProvider(): iterable
    {
        yield 'MySQL 5.7' => ['5.7.44-log', 'SHOW MASTER STATUS'];
        yield 'MySQL 8.0' => ['8.0.46', 'SHOW MASTER STATUS'];
        yield 'MySQL 8.4' => ['8.4.13', 'SHOW BINARY LOG STATUS'];
        yield 'MySQL 9.7' => ['9.7.4', 'SHOW BINARY LOG STATUS'];
        yield 'MySQL 26.10' => ['26.10.0', 'SHOW BINARY LOG STATUS'];
        yield 'MariaDB 10.6' => ['10.6.24-MariaDB-log', 'SHOW MASTER STATUS'];
        yield 'MariaDB 11.4' => ['11.4.13-MariaDB-log', 'SHOW MASTER STATUS'];
    }

    #[DataProvider('masterStatusQueryProvider')]
    public function testShouldGetMasterStatusWithVersionSpecificQuery(string $version, string $expectedQuery): void
    {
        $status = [
            'File' => 'binlog.000002',
            'Position' => 4,
            'Binlog_Do_DB' => '',
            'Binlog_Ignore_DB' => '',
            'Executed_Gtid_Set' => '',
        ];

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))
            ->method('fetchAssociative')
            ->willReturnCallback(static fn (string $query): array => match ($query) {
                'SHOW VARIABLES LIKE "version"' => [
                    'Value' => $version,
                ],
                $expectedQuery => $status,
                default => throw new RuntimeException('Unexpected query: ' . $query),
            });

        $repository = new MySQLRepository($connection);

        self::assertEquals(MasterStatusDTO::makeFromArray($status), $repository->getMasterStatus());
    }

    public function testShouldReconnect(): void
    {
        // just to cover private getConnection
        $exception = $this->createStub(ConnectionException::class);

        $this->connection->method('executeQuery')
            ->willThrowException($exception);

        $this->connection->method('fetchAssociative')
            ->willReturn([
                'Value' => 'NONE',
            ]);

        $this->mySQLRepositoryTest->isCheckSum();
        self::assertTrue(true);
    }
}
