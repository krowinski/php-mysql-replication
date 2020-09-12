<?php
declare(strict_types=1);

namespace BinaryDataReader\Unit;

use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Tests\Unit\BaseTest;

class ConfigTest extends BaseTest
{
    public function shouldMakeConfig(): void
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
    public function shouldCheckDataBasesOnly(): void
    {
        (new ConfigBuilder())->withDatabasesOnly(['boo'])->build();
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
    public function shouldCheckTablesOnly(): void
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
    public function shouldCheckEvent(): void
    {
        self::assertTrue(Config::checkEvent(1));

        (new ConfigBuilder())->withEventsOnly([2])->build();
        self::assertTrue(Config::checkEvent(2));

        (new ConfigBuilder())->withEventsOnly([3])->build();
        self::assertFalse(Config::checkEvent(4));

        (new ConfigBuilder())->withEventsIgnore([4])->build();
        self::assertFalse(Config::checkEvent(4));
    }

    public function shouldCheckHeartbeatPeriodProvider(): array
    {
        return [
            [0],
            [0.0],
            [0.001],
            [4294967],
            [2],
        ];
    }

    /**
     * @test
     * @dataProvider shouldCheckHeartbeatPeriodProvider
     */
    public function shouldCheckHeartbeatPeriod($heartbeatPeriod): void
    {
        $config = (new ConfigBuilder())->withHeartbeatPeriod($heartbeatPeriod)->build();
        $config::validate();

        self::assertSame((float) $heartbeatPeriod, $config::getHeartbeatPeriod());
    }

    public function shouldValidateProvider(): array
    {
        return [
            ['host', 'aaa', ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE],
            ['port', -1, ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE],
            ['slaveId', -1, ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE],
            ['gtid', '-1', ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE],
            ['binLogPosition', -1, ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE, ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE],
            ['tableCacheSize', -1, ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE, ConfigException::TABLE_CACHE_SIZE_ERROR_CODE],
            ['heartbeatPeriod', 4294968, ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE],
            ['heartbeatPeriod', -1, ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE, ConfigException::HEARTBEAT_PERIOD_ERROR_CODE],
        ];
    }

    /**
     * @test
     * @dataProvider shouldValidateProvider
     */
    public function shouldValidate(string $configKey, $configValue, string $expectedMessage, int $expectedCode): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        /** @var Config $config */
        $config = (new ConfigBuilder())->{'with' . strtoupper($configKey)}($configValue)->build();
        $config::validate();
    }

}