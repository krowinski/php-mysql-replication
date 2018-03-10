<?php

namespace BinaryDataReader\Unit;

use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Config\ConfigFactory;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class ConfigTest
 * @package BinaryDataReader\Unit
 */
class ConfigTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakeConfig()
    {
        $expected = [
            'user'            => 'foo',
            'host'            => '127.0.0.1',
            'port'            => 3308,
            'password'        => 'secret',
            'charset'         => 'utf8',
            'gtid'            => '9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592',
            'slaveId'         => 1,
            'binLogFileName'  => 'binfile1.bin',
            'binLogPosition'  => 666,
            'eventsOnly'      => [],
            'eventsIgnore'    => [],
            'tablesOnly'      => ['test_table'],
            'databasesOnly'   => ['test_database'],
            'mariaDbGtid'     => '123:123',
            'tableCacheSize'  => 777,
            'custom'          => [['random' => 'data']],
            'heartbeatPeriod' => 69,
        ];

        $config = new Config(
            $expected['user'],
            $expected['host'],
            $expected['port'],
            $expected['password'],
            $expected['charset'],
            $expected['gtid'],
            $expected['mariaDbGtid'],
            $expected['slaveId'],
            $expected['binLogFileName'],
            $expected['binLogPosition'],
            $expected['eventsOnly'],
            $expected['eventsIgnore'],
            $expected['tablesOnly'],
            $expected['databasesOnly'],
            $expected['tableCacheSize'],
            $expected['custom'],
            $expected['heartbeatPeriod']
        );

        self::assertSame($expected['user'], $config::getUser());
        self::assertSame($expected['host'], $config::getHost());
        self::assertSame($expected['port'], $config::getPort());
        self::assertSame($expected['password'], $config::getPassword());
        self::assertSame($expected['charset'], $config::getCharset());
        self::assertSame($expected['gtid'], $config::getGtid());
        self::assertSame($expected['slaveId'], $config::getSlaveId());
        self::assertSame($expected['binLogFileName'], $config::getBinLogFileName());
        self::assertSame($expected['binLogPosition'], $config::getBinLogPosition());
        self::assertSame($expected['eventsOnly'], $config::getEventsOnly());
        self::assertSame($expected['eventsIgnore'], $config::getEventsIgnore());
        self::assertSame($expected['tablesOnly'], $config::getTablesOnly());
        self::assertSame($expected['mariaDbGtid'], $config::getMariaDbGtid());
        self::assertSame($expected['tableCacheSize'], $config::getTableCacheSize());
        self::assertSame($expected['custom'], $config::getCustom());
        self::assertSame($expected['heartbeatPeriod'], $config::getHeartbeatPeriod());
        self::assertSame($expected['databasesOnly'], $config::getDatabasesOnly());

        $config::validate();
    }

    /**
     * @test
     */
    public function shouldCheckDataBasesOnly()
    {
        self::assertTrue(Config::checkDataBasesOnly('foo'));

        (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
        self::assertFalse(Config::checkDataBasesOnly('foo'));

        (new ConfigBuilder())->withDatabasesOnly(['test'])->build();
        self::assertFalse(Config::checkDataBasesOnly('test'));

        (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
        self::assertTrue(Config::checkDataBasesOnly('bar'));
    }

    /**
     * @test
     */
    public function shouldCheckTablesOnly()
    {
        self::assertFalse(Config::checkTablesOnly('foo'));

        (new ConfigBuilder())->withTablesOnly(['foo'])->build();
        self::assertFalse(Config::checkTablesOnly('foo'));

        (new ConfigBuilder())->withTablesOnly(['test'])->build();
        self::assertFalse(Config::checkTablesOnly('test'));

        (new ConfigBuilder())->withTablesOnly(['foo'])->build();
        self::assertTrue(Config::checkTablesOnly('bar'));
    }

    /**
     * @test
     */
    public function shouldCheckEvent()
    {
        self::assertTrue(Config::checkEvent('foo'));

        (new ConfigBuilder())->withEventsOnly(['bar'])->build();
        self::assertTrue(Config::checkEvent('bar'));

        (new ConfigBuilder())->withEventsOnly(['foo1'])->build();
        self::assertFalse(Config::checkEvent('bar1'));

        (new ConfigBuilder())->withEventsIgnore(['foo2'])->build();
        self::assertFalse(Config::checkEvent('foo2'));
    }

    /**
     * @test
     * @dataProvider shouldValidateProvider
     * @param string $configKey
     * @param mixed $configValue
     * @param string $expectedMessage
     * @param int $expectedCode
     */
    public function shouldValidate($configKey, $configValue, $expectedMessage, $expectedCode)
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        $config = ConfigFactory::makeConfigFromArray([$configKey => $configValue]);
        $config::validate();
    }

    /**
     * @return array
     */
    public function shouldValidateProvider()
    {
        return [
            ['user', 1, ConfigException::USER_ERROR_MESSAGE, ConfigException::USER_ERROR_CODE],
            ['host', 'aaa', ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE],
            ['port', -1, ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE],
            ['password', new \stdClass(), ConfigException::PASSWORD_ERROR_MESSAGE, ConfigException::PASSWORD_ERROR_CODE],
            ['charset', -1, ConfigException::CHARSET_ERROR_MESSAGE, ConfigException::CHARSET_ERROR_CODE],
            ['gtid', -1, ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE],
            ['slaveId', -1, ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE],
            ['binLogFileName', -1, ConfigException::BIN_LOG_FILE_NAME_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_NAME_ERROR_CODE],
            ['binLogPosition', -1, ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE],
            ['mariaDbGtid', -1, ConfigException::MARIADBGTID_ERROR_MESSAGE, ConfigException::MARIADBGTID_ERROR_CODE],
            ['tableCacheSize', -1, ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE],
            ['heartbeatPeriod', 0.5, ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE],
            ['heartbeatPeriod', 4294968, ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE],
            ['heartbeatPeriod', -1, ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE],
        ];
    }
}