<?php

namespace MySQLReplication\Integration;

use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigFactory;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Class BenchmarkEventSubscribers
 * @package example
 */
class MyEventSubscribers extends EventSubscribers
{
    /**
     * @var TypesTest
     */
    private $typesTest;

    public function __construct(TypesTest $typesTest)
    {
        $this->typesTest = $typesTest;
    }

    /**
     * @param EventDTO $event (your own handler more in EventSubscribers class )
     */
    public function allEvents(EventDTO $event)
    {
        $this->typesTest->setEvent($event);
    }
}

/**
 * Class TypesTest
 * @package Tests\Integration
 */
class TypesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $database = 'mysqlreplication_test';
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;
    /**
     * @var \MySQLReplication\MySQLReplicationFactory
     */
    private $binLogStream;
    /**
     * @var EventDTO
     */
    private $currentEvent;

    protected function setUp()
    {
        parent::setUp();

        $config = (new ConfigBuilder())
            ->withUser('root')
            ->withHost('127.0.0.1')
            ->withPassword('root')
            ->build();
        $this->binLogStream = new MySQLReplicationFactory($config);
        $this->binLogStream->registerSubscriber(new MyEventSubscribers($this));

        $this->conn = $this->binLogStream->getDbConnection();

        $this->conn->exec('SET GLOBAL time_zone = "UTC"');
        $this->conn->exec('DROP DATABASE IF EXISTS ' . $this->database);
        $this->conn->exec('CREATE DATABASE ' . $this->database);
        $this->conn->exec('USE ' . $this->database);
        $this->conn->exec('SET SESSION sql_mode = \'\';');
    }

    /**
     * @param EventDTO $eventDTO
     * @return EventDTO
     */
    public function setEvent(EventDTO $eventDTO)
    {
        $this->currentEvent = $eventDTO;
    }

    /**
     * @return EventDTO
     */
    public function getEvent()
    {
        $this->binLogStream->binLogEvent();
        return $this->currentEvent;
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->binLogStream = null;
        $this->conn = null;
    }

    /**
     * @param $create_query
     * @param $insert_query
     * @return \MySQLReplication\Event\DTO\DeleteRowsDTO|\MySQLReplication\Event\DTO\EventDTO|\MySQLReplication\Event\DTO\GTIDLogDTO|\MySQLReplication\Event\DTO\QueryDTO|\MySQLReplication\Event\DTO\RotateDTO|\MySQLReplication\Event\DTO\TableMapDTO|\MySQLReplication\Event\DTO\UpdateRowsDTO|\MySQLReplication\Event\DTO\WriteRowsDTO|\MySQLReplication\Event\DTO\XidDTO
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createAndInsertValue($create_query, $insert_query)
    {
        $this->conn->exec($create_query);
        $this->conn->exec($insert_query);

        self::assertEquals(null, $this->getEvent());
    //    self::assertInstanceOf('MySQLReplication\Event\DTO\GTIDLogDTO', $this->getEvent());
        self::assertInstanceOf('MySQLReplication\Event\DTO\QueryDTO', $this->getEvent());
    //    self::assertInstanceOf('MySQLReplication\Event\DTO\GTIDLogDTO', $this->getEvent());
        self::assertInstanceOf('MySQLReplication\Event\DTO\QueryDTO', $this->getEvent());
      //  self::assertInstanceOf('MySQLReplication\Event\DTO\GTIDLogDTO', $this->getEvent());
        self::assertInstanceOf('MySQLReplication\Event\DTO\QueryDTO', $this->getEvent());
      //  self::assertInstanceOf('MySQLReplication\Event\DTO\GTIDLogDTO', $this->getEvent());
        self::assertInstanceOf('MySQLReplication\Event\DTO\QueryDTO', $this->getEvent());
        self::assertInstanceOf('MySQLReplication\Event\DTO\TableMapDTO', $this->getEvent());

        return $this->getEvent();
    }

    /**
     * @test
     */
    public function shouldBeDecimal()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(2,1))";
        $insert_query = "INSERT INTO test VALUES(4.2)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(4.2, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalLongValues()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(9000000123.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('9000000123.123456', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalLongValues2()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(9000000123.0000012345)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('9000000123.0000012345', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalNegativeValues()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(-42000.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-42000.123456', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalTwoValues()
    {
        $create_query = "CREATE TABLE test ( test DECIMAL(2,1), test2 DECIMAL(20,10) )";
        $insert_query = "INSERT INTO test VALUES(4.2, 42000.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('4.2', $event->getValues()[0]['test']);
        self::assertEquals('42000.123456', $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale1()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(10)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('10', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale2()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(12345678912345678912345)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('12345678912345678912345', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale3()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(100000.0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('100000', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale4()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(-100000.0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-100000.0', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale5()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(-1234567891234567891234)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-1234567891234567891234', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeTinyInt()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test TINYINT)";
        $insert_query = "INSERT INTO test VALUES(255, -128)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(255, $event->getValues()[0]['id']);
        self::assertEquals(-128, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToBooleanTrue()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, TRUE)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->getValues()[0]['id']);
        self::assertEquals(1, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToBooleanFalse()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, FALSE)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->getValues()[0]['id']);
        self::assertEquals(0, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToNone()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, NULL)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->getValues()[0]['id']);
        self::assertEquals(null, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToShort()
    {
        $create_query = "CREATE TABLE test (id SMALLINT UNSIGNED NOT NULL, test SMALLINT)";
        $insert_query = "INSERT INTO test VALUES(65535, -32768)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(65535, $event->getValues()[0]['id']);
        self::assertEquals(-32768, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeLong()
    {
        $create_query = "CREATE TABLE test (id INT UNSIGNED NOT NULL, test INT)";
        $insert_query = "INSERT INTO test VALUES(4294967295, -2147483648)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(4294967295, $event->getValues()[0]['id']);
        self::assertEquals(-2147483648, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeFloat()
    {
        $create_query = "CREATE TABLE test (id FLOAT NOT NULL, test FLOAT)";
        $insert_query = "INSERT INTO test VALUES(42.42, -84.84)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(42.42, $event->getValues()[0]['id']);
        self::assertEquals(-84.84, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDouble()
    {
        $create_query = "CREATE TABLE test (id DOUBLE NOT NULL, test DOUBLE)";
        $insert_query = "INSERT INTO test VALUES(42.42, -84.84)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(42.42, $event->getValues()[0]['id']);
        self::assertEquals(-84.84, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeTimestamp()
    {
        $create_query = "CREATE TABLE test (test TIMESTAMP);";
        $insert_query = "INSERT INTO test VALUES('1984-12-03 12:33:07')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeTimestampMySQL56()
    {
        $create_query = "CREATE TABLE test (test0 TIMESTAMP(0),
            test1 TIMESTAMP(1),
            test2 TIMESTAMP(2),
            test3 TIMESTAMP(3),
            test4 TIMESTAMP(4),
            test5 TIMESTAMP(5),
            test6 TIMESTAMP(6));";
        $insert_query = "INSERT INTO test VALUES(
            '1984-12-03 12:33:07',
            '1984-12-03 12:33:07.1',
            '1984-12-03 12:33:07.12',
            '1984-12-03 12:33:07.123',
            '1984-12-03 12:33:07.1234',
            '1984-12-03 12:33:07.12345',
            '1984-12-03 12:33:07.123456')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->getValues()[0]['test0']);
        self::assertEquals('1984-12-03 12:33:07.1', $event->getValues()[0]['test1']);
        self::assertEquals('1984-12-03 12:33:07.12', $event->getValues()[0]['test2']);
        self::assertEquals('1984-12-03 12:33:07.123', $event->getValues()[0]['test3']);
        self::assertEquals('1984-12-03 12:33:07.1234', $event->getValues()[0]['test4']);
        self::assertEquals('1984-12-03 12:33:07.12345', $event->getValues()[0]['test5']);
        self::assertEquals('1984-12-03 12:33:07.123456', $event->getValues()[0]['test6']);
    }

    /**
     * @test
     */
    public function shouldBeLongLong()
    {
        $create_query = "CREATE TABLE test (id BIGINT UNSIGNED NOT NULL, test BIGINT)";
        $insert_query = "INSERT INTO test VALUES(18446744073709551615, -9223372036854775808)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('18446744073709551615', $event->getValues()[0]['id']);
        self::assertEquals('-9223372036854775808', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeInt24()
    {
        $create_query = "CREATE TABLE test (id MEDIUMINT UNSIGNED NOT NULL, test MEDIUMINT, test2 MEDIUMINT, test3 MEDIUMINT, test4 MEDIUMINT, test5 MEDIUMINT)";
        $insert_query = "INSERT INTO test VALUES(16777215, 8388607, -8388608, 8, -8, 0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(16777215, $event->getValues()[0]['id']);
        self::assertEquals(8388607, $event->getValues()[0]['test']);
        self::assertEquals(-8388608, $event->getValues()[0]['test2']);
        self::assertEquals(8, $event->getValues()[0]['test3']);
        self::assertEquals(-8, $event->getValues()[0]['test4']);
        self::assertEquals(0, $event->getValues()[0]['test5']);
    }

    /**
     * @test
     */
    public function shouldBeDate()
    {
        $create_query = "CREATE TABLE test (test DATE);";
        $insert_query = "INSERT INTO test VALUES('1984-12-03')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeZeroDate()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test DATE, test2 DATE);";
        $insert_query = "INSERT INTO test (id, test2) VALUES(1, '0000-01-21')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
        self::assertNull($event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeZeroMonth()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test DATE, test2 DATE);";
        $insert_query = "INSERT INTO test (id, test2) VALUES(1, '2015-00-21')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
        self::assertNull($event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeZeroDay()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test DATE, test2 DATE);";
        $insert_query = "INSERT INTO test (id, test2) VALUES(1, '2015-05-00')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
        self::assertNull($event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeTime()
    {
        $create_query = "CREATE TABLE test (test TIME);";
        $insert_query = "INSERT INTO test VALUES('12:33:18')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('12:33:18', $event->getValues()[0]['test']);
    }


    /**
     * @test
     */
    public function shouldBeZeroTime()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test TIME NOT NULL DEFAULT 0);";
        $insert_query = "INSERT INTO test (id) VALUES(1)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('00:00:00', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDateTime()
    {
        $create_query = "CREATE TABLE test (test DATETIME);";
        $insert_query = "INSERT INTO test VALUES('1984-12-03 12:33:07')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeZeroDateTime()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test DATETIME NOT NULL DEFAULT 0);";
        $insert_query = "INSERT INTO test (id) VALUES(1)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeBrokenDateTime()
    {
        $create_query = "CREATE TABLE test (test DATETIME NOT NULL);";
        $insert_query = "INSERT INTO test VALUES('2013-00-00 00:00:00')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeYear()
    {
        $create_query = "CREATE TABLE test (test YEAR(4), test2 YEAR)";
        $insert_query = "INSERT INTO test VALUES(1984, 1984)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1984, $event->getValues()[0]['test']);
        self::assertEquals(1984, $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeVarChar()
    {
        $create_query = "CREATE TABLE test (test VARCHAR(242)) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeBit()
    {
        $create_query =  "CREATE TABLE test (
            test BIT(6),
            test2 BIT(16),
            test3 BIT(12),
            test4 BIT(9),
            test5 BIT(64)
         );";
        $insert_query = "INSERT INTO test VALUES(
            b'100010',
            b'1000101010111000',
            b'100010101101',
            b'101100111',
            b'1101011010110100100111100011010100010100101110111011101011011010'
        )";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('100010', $event->getValues()[0]['test']);
        self::assertEquals('1000101010111000', $event->getValues()[0]['test2']);
        self::assertEquals('100010101101', $event->getValues()[0]['test3']);
        self::assertEquals('101100111', $event->getValues()[0]['test4']);
        self::assertEquals('1101011010110100100111100011010100010100101110111011101011011010', $event->getValues()[0]['test5']);
    }

    /**
     * @test
     */
    public function shouldBeEnum()
    {
        $create_query = "CREATE TABLE test
            (
                test ENUM('a', 'ba', 'c'),
                test2 ENUM('a', 'ba', 'c'),
                test3 ENUM('foo', 'bar')
            )
            CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('ba', 'a', 'not_exists')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('ba', $event->getValues()[0]['test']);
        self::assertEquals('a', $event->getValues()[0]['test2']);
        self::assertEquals('', $event->getValues()[0]['test3']);
    }

    /**
     * @test
     */
    public function shouldBeSet()
    {
        $create_query = "CREATE TABLE test (test SET('a', 'ba', 'c'), test2 SET('a', 'ba', 'c')) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('ba,a,c', 'a,c')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(['a', 'ba', 'c'], $event->getValues()[0]['test']);
        self::assertEquals(['a', 'c'], $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeTinyBlob()
    {
        $create_query = "CREATE TABLE test (test TINYBLOB, test2 TINYTEXT) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello', 'World')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
        self::assertEquals('World', $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeMediumBlob()
    {
        $create_query = "CREATE TABLE test (test MEDIUMBLOB, test2 MEDIUMTEXT) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello', 'World')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
        self::assertEquals('World', $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeLongBlob()
    {
        $create_query = "CREATE TABLE test (test LONGBLOB, test2 LONGTEXT) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello', 'World')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
        self::assertEquals('World', $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeBlob()
    {
        $create_query = "CREATE TABLE test (test BLOB, test2 TEXT) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello', 'World')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
        self::assertEquals('World', $event->getValues()[0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeString()
    {
        $create_query = "CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('Hello')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeGeometry()
    {
        $create_query = "CREATE TABLE test (test GEOMETRY);";
        $insert_query = "INSERT INTO test VALUES(GeomFromText('POINT(1 1)'))";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('000000000101000000000000000000f03f000000000000f03f', bin2hex($event->getValues()[0]['test']));
    }

    /**
     * @test
     */
    public function shouldBeNull()
    {
        $create_query = "CREATE TABLE test (
            test TINYINT NULL DEFAULT NULL,
            test2 TINYINT NULL DEFAULT NULL,
            test3 TINYINT NULL DEFAULT NULL,
            test4 TINYINT NULL DEFAULT NULL,
            test5 TINYINT NULL DEFAULT NULL,
            test6 TINYINT NULL DEFAULT NULL,
            test7 TINYINT NULL DEFAULT NULL,
            test8 TINYINT NULL DEFAULT NULL,
            test9 TINYINT NULL DEFAULT NULL,
            test10 TINYINT NULL DEFAULT NULL,
            test11 TINYINT NULL DEFAULT NULL,
            test12 TINYINT NULL DEFAULT NULL,
            test13 TINYINT NULL DEFAULT NULL,
            test14 TINYINT NULL DEFAULT NULL,
            test15 TINYINT NULL DEFAULT NULL,
            test16 TINYINT NULL DEFAULT NULL,
            test17 TINYINT NULL DEFAULT NULL,
            test18 TINYINT NULL DEFAULT NULL,
            test19 TINYINT NULL DEFAULT NULL,
            test20 TINYINT NULL DEFAULT NULL
            )";
        $insert_query = "INSERT INTO test (test, test2, test3, test7, test20) VALUES(NULL, -128, NULL, 42, 84)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->getValues()[0]['test']);
        self::assertEquals(-128, $event->getValues()[0]['test2']);
        self::assertNull($event->getValues()[0]['test3']);
        self::assertEquals(42, $event->getValues()[0]['test7']);
        self::assertEquals(84, $event->getValues()[0]['test20']);

    }

    /**
     * @test
     */
    public function shouldBeEncodedLatin1()
    {
        $this->conn->exec("SET CHARSET latin1");

        $string = "\00e9";

        $create_query = "CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;";
        $insert_query = "INSERT INTO test VALUES('" . $string . "');";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals($string, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeEncodedUTF8()
    {
        $this->conn->exec("SET CHARSET utf8");

        $string = "\20ac";

        $create_query = "CREATE TABLE test (test CHAR(12)) CHARACTER SET utf8 COLLATE utf8_bin;";
        $insert_query = "INSERT INTO test VALUES('" . $string . "');";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals($string, $event->getValues()[0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeJson()
    {
        if (false === strpos($this->conn->fetchColumn('SELECT VERSION()'), '5.7'))
        {
            $this->markTestIncomplete('Only for mysql 5.7');
        }

        $create_query = "create table t1 (i INT, j JSON)";
        $insert_query = "INSERT INTO t1 VALUES 
            (0, NULL) , 
            (1, '{\"a\": 2}'),
            (2, '[1,2]'),
            (3, '{\"a\":\"b\", \"c\":\"d\",\"ab\":\"abc\", \"bc\": [\"x\", \"y\"]}'),
            (4, '[\"here\", [\"I\", \"am\"], \"!!!\"]'),
            (5, '\"scalar string\"'),
            (6, 'true'),
            (7, 'false'),
            (8, 'null'),
            (9, '-1'),
            (10, CAST(CAST(1 AS UNSIGNED) AS JSON)),
            (11, '32767'),
            (12, '32768'),
            (13, '-32768'),
            (14, '-32769'),
            (15, '2147483647'),
            (16, '2147483648'),
            (17, '-2147483648'),
            (18, '-2147483649'),
            (19, '18446744073709551615'),
            (20, '18446744073709551616'),
            (21, '3.14'),
            (22, '{}'),
            (23, '[]'),
            -- (24, CAST(CAST('2015-01-15 23:24:25' AS DATETIME) AS JSON)),
            -- (25, CAST(CAST('23:24:25' AS TIME) AS JSON)),
            -- (125, CAST(CAST('23:24:25.12' AS TIME(3)) AS JSON)),
            -- (225, CAST(CAST('23:24:25.0237' AS TIME(3)) AS JSON)),
            -- (26, CAST(CAST('2015-01-15' AS DATE) AS JSON)),
            -- (27, CAST(TIMESTAMP'2015-01-15 23:24:25' AS JSON)),
            -- (127, CAST(TIMESTAMP'2015-01-15 23:24:25.12' AS JSON)),
            -- (227, CAST(TIMESTAMP'2015-01-15 23:24:25.0237' AS JSON)),
            -- (327, CAST(UNIX_TIMESTAMP('2015-01-15 23:24:25') AS JSON)),
            -- (28, CAST(ST_GeomFromText('POINT(1 1)') AS JSON)),
            (29, CAST('[]' AS CHAR CHARACTER SET 'ascii')),
            -- (30, CAST(x'cafe' AS JSON)),
            -- (31, CAST(x'cafebabe' AS JSON)),
            (100, CONCAT('{\"', REPEAT('a', 64 * 1024 - 1), '\":123}'))
        ";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $results = $event->getValues();

        self::assertEquals($results[0]['j'], null);
        self::assertEquals($results[1]['j'], '{"a":2}');
        self::assertEquals($results[2]['j'], '[1,2]');
        self::assertEquals($results[3]['j'], '{"a":"b","c":"d","ab":"abc","bc":["x","y"]}');
        self::assertEquals($results[4]['j'], '["here",["I","am"],"!!!"]');
        self::assertEquals($results[5]['j'], '"scalar string"');
        self::assertEquals($results[6]['j'], 'true');
        self::assertEquals($results[7]['j'], 'false');
        self::assertEquals($results[8]['j'], '"null"');
        self::assertEquals($results[9]['j'], '"-1"');
        self::assertEquals($results[10]['j'], '"1"');
        self::assertEquals($results[11]['j'], '"32767"');
        self::assertEquals($results[12]['j'], '"32768"');
        self::assertEquals($results[13]['j'], '"-32768"');
        self::assertEquals($results[14]['j'], '"-32769"');
        self::assertEquals($results[15]['j'], '"2147483647"');
        self::assertEquals($results[16]['j'], '"2147483648"');
        self::assertEquals($results[17]['j'], '"-2147483648"');
        self::assertEquals($results[18]['j'], '"-2147483649"');
        self::assertEquals($results[19]['j'], '"18446744073709551615"');
        self::assertEquals($results[20]['j'], '"1.844674407371E+19"');
        self::assertEquals($results[21]['j'], '"3.14"');
        self::assertEquals($results[22]['j'], '{}');
        self::assertEquals($results[23]['j'], '[]');
        self::assertEquals($results[24]['j'], '[]');
    }
}