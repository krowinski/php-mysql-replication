<?php

namespace Tests;

use MySQLReplication\Definitions\ConstEventType;

/**
 * Class Base
 */
class TypesTest extends \PHPUnit_Framework_TestCase
{
    private $database = 'mysqlreplication_test';

    private function createAndInsertValue($create_query, $insert_query)
    {
        $conn = \MySQLReplication\DataBase\DBHelper::getConnection();
        $binLogStream = new \MySQLReplication\Service\BinLogStream();

        $conn->exec("SET GLOBAL time_zone = 'UTC'");
        $conn->exec("DROP DATABASE IF EXISTS " . $this->database);
        $conn->exec("CREATE DATABASE " . $this->database);
        $conn->exec("USE " . $this->database);
        $conn->exec($create_query);
        $conn->exec($insert_query);

        $this->assertEquals(ConstEventType::ROTATE_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::FORMAT_DESCRIPTION_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::GTID_LOG_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::QUERY_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::GTID_LOG_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::QUERY_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::GTID_LOG_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::QUERY_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::GTID_LOG_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::QUERY_EVENT, $binLogStream->analysisBinLog()['event']['type']);
        $this->assertEquals(ConstEventType::TABLE_MAP_EVENT, $binLogStream->analysisBinLog()['event']['type']);

        return $binLogStream->analysisBinLog();
    }

    /**
     * @test
     */
    public function shouldBeDecimal()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(2,1))";
        $insert_query = "INSERT INTO test VALUES(4.2)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(4.2, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalLongValues()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(9000000123.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('9000000123.123456', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalLongValues2()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(9000000123.0000012345)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('9000000123.0000012345', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalNegativeValues()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(20,10))";
        $insert_query = "INSERT INTO test VALUES(-42000.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('-42000.123456', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalTwoValues()
    {
        $create_query = "CREATE TABLE test ( test DECIMAL(2,1), test2 DECIMAL(20,10) )";
        $insert_query = "INSERT INTO test VALUES(4.2, 42000.123456)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('4.2', $event['add'][0]['test']);
        $this->assertEquals('42000.123456', $event['add'][0]['test2']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale1()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(10)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('10', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale2()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(12345678912345678912345)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('12345678912345678912345', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale3()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(100000.0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('100000', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale4()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(-100000.0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('-100000.0', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDecimalZeroScale5()
    {
        $create_query = "CREATE TABLE test (test DECIMAL(23,0))";
        $insert_query = "INSERT INTO test VALUES(-1234567891234567891234)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('-1234567891234567891234', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeTinyInt()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test TINYINT)";
        $insert_query = "INSERT INTO test VALUES(255, -128)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(255, $event['add'][0]['id']);
        $this->assertEquals(-128, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToBooleanTrue()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, TRUE)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(1, $event['add'][0]['id']);
        $this->assertEquals(1, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToBooleanFalse()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, FALSE)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(1, $event['add'][0]['id']);
        $this->assertEquals(0, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToNone()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)";
        $insert_query = "INSERT INTO test VALUES(1, NULL)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(1, $event['add'][0]['id']);
        $this->assertEquals(null, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeMapsToShort()
    {
        $create_query = "CREATE TABLE test (id SMALLINT UNSIGNED NOT NULL, test SMALLINT)";
        $insert_query = "INSERT INTO test VALUES(65535, -32768)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(65535, $event['add'][0]['id']);
        $this->assertEquals(-32768, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeLong()
    {
        $create_query = "CREATE TABLE test (id INT UNSIGNED NOT NULL, test INT)";
        $insert_query = "INSERT INTO test VALUES(4294967295, -2147483648)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(4294967295, $event['add'][0]['id']);
        $this->assertEquals(-2147483648, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeFloat()
    {
        $create_query = "CREATE TABLE test (id FLOAT NOT NULL, test FLOAT)";
        $insert_query = "INSERT INTO test VALUES(42.42, -84.84)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(42.42, $event['add'][0]['id']);
        $this->assertEquals(-84.84, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeDouble()
    {
        $create_query = "CREATE TABLE test (id DOUBLE NOT NULL, test DOUBLE)";
        $insert_query = "INSERT INTO test VALUES(42.42, -84.84)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(42.42, $event['add'][0]['id']);
        $this->assertEquals(-84.84, $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeTimestamp()
    {
        $create_query = "CREATE TABLE test (test TIMESTAMP);";
        $insert_query = "INSERT INTO test VALUES('1984-12-03 12:33:07')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('1984-12-03 12:33:07', $event['add'][0]['test']);
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

        $this->assertEquals('1984-12-03 12:33:07', $event['add'][0]['test0']);
        $this->assertEquals('1984-12-03 12:33:07.1', $event['add'][0]['test1']);
        $this->assertEquals('1984-12-03 12:33:07.12', $event['add'][0]['test2']);
        $this->assertEquals('1984-12-03 12:33:07.123', $event['add'][0]['test3']);
        $this->assertEquals('1984-12-03 12:33:07.1234', $event['add'][0]['test4']);
        $this->assertEquals('1984-12-03 12:33:07.12345', $event['add'][0]['test5']);
        $this->assertEquals('1984-12-03 12:33:07.123456', $event['add'][0]['test6']);
    }

    /**
     * @test
     */
    public function shouldBeLongLong()
    {
        $create_query = "CREATE TABLE test (id BIGINT UNSIGNED NOT NULL, test BIGINT)";
        $insert_query = "INSERT INTO test VALUES(18446744073709551615, -9223372036854775808)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('18446744073709551615', $event['add'][0]['id']);
        $this->assertEquals('-9223372036854775808', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeInt24()
    {
        $create_query = "CREATE TABLE test (id MEDIUMINT UNSIGNED NOT NULL, test MEDIUMINT, test2 MEDIUMINT, test3 MEDIUMINT, test4 MEDIUMINT, test5 MEDIUMINT)";
        $insert_query = "INSERT INTO test VALUES(16777215, 8388607, -8388608, 8, -8, 0)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(16777215, $event['add'][0]['id']);
        $this->assertEquals(8388607, $event['add'][0]['test']);
        $this->assertEquals(-8388608, $event['add'][0]['test2']);
        $this->assertEquals(8, $event['add'][0]['test3']);
        $this->assertEquals(-8, $event['add'][0]['test4']);
        $this->assertEquals(0, $event['add'][0]['test5']);
    }

    /**
     * @test
     */
    public function shouldBeDate()
    {
        $create_query = "CREATE TABLE test (test DATE);";
        $insert_query = "INSERT INTO test VALUES('1984-12-03')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals('1984-12-03', $event['add'][0]['test']);
    }

    /**
     * @test
     */
    public function shouldBeZeroDate()
    {
        $create_query = "CREATE TABLE test (id INTEGER, test DATE, test2 DATE);";
        $insert_query = "INSERT INTO test (id, test2) VALUES(1, '0000-01-21')";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(null, $event['add'][0]['test']);
        $this->assertEquals(null, $event['add'][0]['test2']);
    }

    


}