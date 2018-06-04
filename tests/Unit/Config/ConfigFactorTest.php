<?php

namespace BinaryDataReader\Unit;

use MySQLReplication\Config\ConfigFactory;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class ConfigFactorTest
 * @package BinaryDataReader\Unit
 */
class ConfigFactorTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakeConfigFromArray(): void
    {
         $expected = [
            'user' => 'foo',
            'host' => '127.0.0.1',
            'port' => 3308,
            'password' => 'secret',
            'charset' => 'utf8',
            'gtid' => '9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592',
            'slaveId' => 1,
            'binLogFileName' => 'binfile1.bin',
            'binLogPosition' => 666,
            'eventsOnly' => [],
            'eventsIgnore' => [],
            'tablesOnly' => ['test_table'],
            'databasesOnly' => ['test_database'],
            'mariaDbGtid' => '123:123',
            'tableCacheSize' => 777,
            'custom' => [['random' => 'data']],
            'heartbeatPeriod' => 69,
        ];

        $config = ConfigFactory::makeConfigFromArray($expected);

        self::assertSame(json_encode($expected), json_encode($config));
    }
}