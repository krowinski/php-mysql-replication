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
    public function shouldBeTinyInt()
    {
        $create_query = "CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test TINYINT)";
        $insert_query = "INSERT INTO test VALUES(255, -128)";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $this->assertEquals(255, $event['add'][0]['id']);
        $this->assertEquals(-128, $event['add'][0]['test']);

    }


}