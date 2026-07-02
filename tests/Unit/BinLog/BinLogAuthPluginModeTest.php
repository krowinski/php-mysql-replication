<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\BinLog;

use MySQLReplication\BinLog\BinLogAuthPluginMode;
use MySQLReplication\Exception\MySQLReplicationException;
use PHPUnit\Framework\TestCase;

class BinLogAuthPluginModeTest extends TestCase
{
    public function testShouldMakeMysqlNativePassword(): void
    {
        $mode = BinLogAuthPluginMode::make('mysql_native_password');
        self::assertSame(BinLogAuthPluginMode::MysqlNativePassword, $mode);
    }

    public function testShouldMakeCachingSha2Password(): void
    {
        $mode = BinLogAuthPluginMode::make('caching_sha2_password');
        self::assertSame(BinLogAuthPluginMode::CachingSha2Password, $mode);
    }

    public function testShouldThrowExceptionForUnsupportedPlugin(): void
    {
        $this->expectException(MySQLReplicationException::class);
        $this->expectExceptionMessage(MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED);
        $this->expectExceptionCode(MySQLReplicationException::BINLOG_AUTH_NOT_SUPPORTED_CODE);

        BinLogAuthPluginMode::make('unknown_plugin');
    }

    public function testShouldHaveCorrectValues(): void
    {
        self::assertSame('mysql_native_password', BinLogAuthPluginMode::MysqlNativePassword->value);
        self::assertSame('caching_sha2_password', BinLogAuthPluginMode::CachingSha2Password->value);
    }
}
