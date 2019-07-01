<?php


namespace MySQLReplication\Tests\Unit\Repository;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\MasterStatusDTO;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class MySQLRepositoryTest
 * @package MySQLReplication\Tests\Unit\Repository
 */
class MySQLRepositoryTest extends BaseTest
{
    /**
     * @var MySQLRepository
     */
    private $mySQLRepositoryTest;
    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    public function setUp()
    {
        parent::setUp();

        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->mySQLRepositoryTest = new MySQLRepository($this->connection);
    }

    /**
     * @test
     */
    public function shouldGetFields(): void
    {
        $expected = [
            [
                'COLUMN_NAME' => 'cname',
                'COLLATION_NAME' => 'colname',
                'CHARACTER_SET_NAME' => 'charname',
                'COLUMN_COMMENT' => 'colcommnet',
                'COLUMN_TYPE' => 'coltype',
                'COLUMN_KEY' => 'colkey'
            ]
        ];

        $this->connection->method('fetchAll')->willReturn($expected);

        self::assertEquals(FieldDTOCollection::makeFromArray($expected), $this->mySQLRepositoryTest->getFields('foo', 'bar'));
    }

    /**
     * @test
     * @throws DBALException
     */
    public function shouldIsCheckSum(): void
    {
        self::assertFalse($this->mySQLRepositoryTest->isCheckSum());

        $this->connection->method('fetchAssoc')->willReturnOnConsecutiveCalls(
            ['Value' => 'CRC32'],
            ['Value' => 'NONE']
        );

        self::assertTrue($this->mySQLRepositoryTest->isCheckSum());
        self::assertFalse($this->mySQLRepositoryTest->isCheckSum());
    }

    /**
     * @test
     */
    public function shouldGetVersion(): void
    {
        $expected = [
            ['Value' => 'foo'],
            ['Value' => 'bar'],
            ['Value' => '123'],
        ];

        $this->connection->method('fetchAll')->willReturn($expected);

        self::assertEquals('foobar123', $this->mySQLRepositoryTest->getVersion());
    }

    /**
     * @test
     */
    public function shouldGetMasterStatus(): void
    {
        $expected = [
            'File' => 'mysql-bin.000002',
            'Position' => 4587305,
            'Binlog_Do_DB' => '',
            'Binlog_Ignore_DB' => '',
            'Executed_Gtid_Set' => '041de05f-a36a-11e6-bc73-000c2976f3f3:1-8023',
        ];

        $this->connection->method('fetchAssoc')->willReturn($expected);

        self::assertEquals(MasterStatusDTO::makeFromArray($expected), $this->mySQLRepositoryTest->getMasterStatus());
    }

    /**
     * @test
     */
    public function shouldDestroy(): void
    {
        $this->mySQLRepositoryTest = null;
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function shouldReconnect(): void
    {
        // just to cover private getConnection
        $this->connection->method('ping')->willReturn(false);
        $this->mySQLRepositoryTest->isCheckSum();
        self::assertTrue(true);
    }
}