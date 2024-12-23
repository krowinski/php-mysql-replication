<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Config;

use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
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
            'binLogPosition' => '999',
            'eventsOnly' => [],
            'eventsIgnore' => [],
            'tablesOnly' => ['test_table'],
            'databasesOnly' => ['test_database'],
            'mariaDbGtid' => '123:123',
            'tableCacheSize' => 777,
            'custom' => [
                [
                    'random' => 'data',
                ],
            ],
            'heartbeatPeriod' => 69,
            'slaveUuid' => '6c27ed6d-7ee1-11e3-be39-6c626d957cff',
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
            $expected['heartbeatPeriod'],
            $expected['slaveUuid']
        );

        self::assertSame($expected['user'], $config->user);
        self::assertSame($expected['host'], $config->host);
        self::assertSame($expected['port'], $config->port);
        self::assertSame($expected['password'], $config->password);
        self::assertSame($expected['charset'], $config->charset);
        self::assertSame($expected['gtid'], $config->gtid);
        self::assertSame($expected['slaveId'], $config->slaveId);
        self::assertSame($expected['binLogFileName'], $config->binLogFileName);
        self::assertSame($expected['binLogPosition'], $config->binLogPosition);
        self::assertSame($expected['eventsOnly'], $config->eventsOnly);
        self::assertSame($expected['eventsIgnore'], $config->eventsIgnore);
        self::assertSame($expected['tablesOnly'], $config->tablesOnly);
        self::assertSame($expected['mariaDbGtid'], $config->mariaDbGtid);
        self::assertSame($expected['tableCacheSize'], $config->tableCacheSize);
        self::assertSame($expected['custom'], $config->custom);
        self::assertSame($expected['heartbeatPeriod'], $config->heartbeatPeriod);
        self::assertSame($expected['databasesOnly'], $config->databasesOnly);
        self::assertSame($expected['slaveUuid'], $config->slaveUuid);

        $config->validate();
    }

    public function testShouldCheckDataBasesOnly(): void
    {
        $config = (new ConfigBuilder())->withDatabasesOnly(['boo'])->build();
        self::assertTrue($config->checkDataBasesOnly('foo'));

        $config = (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
        self::assertFalse($config->checkDataBasesOnly('foo'));

        $config = (new ConfigBuilder())->withDatabasesOnly(['test'])->build();
        self::assertFalse($config->checkDataBasesOnly('test'));

        $config = (new ConfigBuilder())->withDatabasesOnly(['foo'])->build();
        self::assertTrue($config->checkDataBasesOnly('bar'));

        $config = (new ConfigBuilder())->withDatabasesRegex(['/^foo_.*/'])->build();
        self::assertFalse($config->checkDataBasesOnly('foo_123'));
    }

    public function testShouldCheckTablesOnly(): void
    {
        $config = (new ConfigBuilder())->build();
        self::assertFalse($config->checkTablesOnly('foo'));

        $config = (new ConfigBuilder())->withTablesOnly(['foo'])->build();
        self::assertFalse($config->checkTablesOnly('foo'));

        $config = (new ConfigBuilder())->withTablesOnly(['test'])->build();
        self::assertFalse($config->checkTablesOnly('test'));

        $config = (new ConfigBuilder())->withTablesOnly(['foo'])->build();
        self::assertTrue($config->checkTablesOnly('bar'));

        $config = (new ConfigBuilder())->withTablesRegex(['/^foo_.*/'])->build();
        self::assertFalse($config->checkTablesOnly('foo_123'));
    }

    public function testShouldCheckEvent(): void
    {
        $config = (new ConfigBuilder())->build();
        self::assertTrue($config->checkEvent(1));

        $config = (new ConfigBuilder())->withEventsOnly([2])->build();
        self::assertTrue($config->checkEvent(2));

        $config = (new ConfigBuilder())->withEventsOnly([3])->build();
        self::assertFalse($config->checkEvent(4));

        $config = (new ConfigBuilder())->withEventsIgnore([4])->build();
        self::assertFalse($config->checkEvent(4));
    }

    public static function shouldCheckHeartbeatPeriodProvider(): array
    {
        return [[0], [0.0], [0.001], [4294967], [2]];
    }

    #[DataProvider('shouldCheckHeartbeatPeriodProvider')] public function testShouldCheckHeartbeatPeriod(
        int|float $heartbeatPeriod
    ): void {
        $config = (new ConfigBuilder())->withHeartbeatPeriod($heartbeatPeriod)
            ->build();
        $config->validate();

        self::assertEquals($heartbeatPeriod, $config->heartbeatPeriod);
    }

    public static function shouldValidateProvider(): array
    {
        return [
            ['host', 'aaa', ConfigException::IP_ERROR_MESSAGE, ConfigException::IP_ERROR_CODE],
            ['port', -1, ConfigException::PORT_ERROR_MESSAGE, ConfigException::PORT_ERROR_CODE],
            ['slaveId', -1, ConfigException::SLAVE_ID_ERROR_MESSAGE, ConfigException::SLAVE_ID_ERROR_CODE],
            ['gtid', '-1', ConfigException::GTID_ERROR_MESSAGE, ConfigException::GTID_ERROR_CODE],
            [
                'binLogPosition',
                '-1',
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_MESSAGE,
                ConfigException::BIN_LOG_FILE_POSITION_ERROR_CODE,
            ],
            [
                'tableCacheSize',
                -1,
                ConfigException::TABLE_CACHE_SIZE_ERROR_MESSAGE,
                ConfigException::TABLE_CACHE_SIZE_ERROR_CODE,
            ],
            [
                'heartbeatPeriod',
                4294968,
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE,
                ConfigException::HEARTBEAT_PERIOD_ERROR_CODE,
            ],
            [
                'heartbeatPeriod',
                -1,
                ConfigException::HEARTBEAT_PERIOD_ERROR_MESSAGE,
                ConfigException::HEARTBEAT_PERIOD_ERROR_CODE,
            ],
        ];
    }

    #[DataProvider('shouldValidateProvider')]
    public function testShouldValidate(
        string $configKey,
        mixed $configValue,
        string $expectedMessage,
        int $expectedCode
    ): void {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        /** @var Config $config */
        $config = (new ConfigBuilder())->{'with' . strtoupper($configKey)}($configValue)->build();
        $config->validate();
    }
}
