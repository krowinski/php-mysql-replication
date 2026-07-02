<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\BinLog;

use MySQLReplication\BinLog\BinLogAuthPluginMode;
use MySQLReplication\BinLog\BinLogServerInfo;
use PHPUnit\Framework\TestCase;

class BinLogServerInfoTest extends TestCase
{
    public function testShouldDetectGenericMySQL(): void
    {
        $info = new BinLogServerInfo(
            10,
            '8.0.28',
            1,
            'salt',
            45,
            BinLogAuthPluginMode::MysqlNativePassword,
            'MySQL',
            8.0
        );

        self::assertTrue($info->isGeneric());
        self::assertFalse($info->isMariaDb());
        self::assertFalse($info->isPercona());
    }

    public function testShouldDetectMariaDB(): void
    {
        $info = new BinLogServerInfo(
            10,
            '10.6.5-MariaDB',
            1,
            'salt',
            45,
            BinLogAuthPluginMode::MysqlNativePassword,
            'MariaDB',
            10.6
        );

        self::assertTrue($info->isMariaDb());
        self::assertFalse($info->isGeneric());
        self::assertFalse($info->isPercona());
    }

    public function testShouldDetectPercona(): void
    {
        $info = new BinLogServerInfo(
            10,
            '8.0.28-Percona',
            1,
            'salt',
            45,
            BinLogAuthPluginMode::MysqlNativePassword,
            'Percona',
            8.0
        );

        self::assertTrue($info->isPercona());
        self::assertFalse($info->isGeneric());
        self::assertFalse($info->isMariaDb());
    }

    public function testShouldExposeProperties(): void
    {
        $info = new BinLogServerInfo(
            10,
            '5.7.30',
            42,
            'mysalt123',
            33,
            BinLogAuthPluginMode::CachingSha2Password,
            'MySQL',
            5.7
        );

        self::assertSame(10, $info->protocolVersion);
        self::assertSame('5.7.30', $info->serverVersion);
        self::assertSame(42, $info->connectionId);
        self::assertSame('mysalt123', $info->salt);
        self::assertSame(33, $info->characterSet);
        self::assertSame(BinLogAuthPluginMode::CachingSha2Password, $info->authPlugin);
        self::assertSame('MySQL', $info->versionName);
        self::assertSame(5.7, $info->versionRevision);
    }

    public function testShouldParseCharacterSetFromRawHandshake(): void
    {
        // Minimal MySQL handshake v10 packet with character_set = 45 (utf8mb4)
        $serverVersion = "8.0.28\0";
        $connectionId = pack('V', 7);          // 4 bytes little-endian
        $salt1 = str_repeat('A', 8);    // auth-plugin-data part 1
        $filler = "\0";
        $capFlags1 = "\xff\xf7";            // capability_flag_1
        $characterSet = chr(45);               // utf8mb4
        $statusFlags = "\x02\x00";
        $capFlags2 = "\xff\x81";
        $saltLen = chr(21);               // auth_plugin_data_len
        $reserved = str_repeat("\0", 10);
        $salt2 = str_repeat('B', 12);   // auth-plugin-data part 2
        $nullByte = "\0";
        $authPlugin = 'mysql_native_password';

        $data = chr(10)                         // protocol version
            . $serverVersion
            . $connectionId
            . $salt1
            . $filler
            . $capFlags1
            . $characterSet
            . $statusFlags
            . $capFlags2
            . $saltLen
            . $reserved
            . $salt2
            . $nullByte
            . $authPlugin
            . "\0";                             // null terminator — parser reads up to length-1

        $info = BinLogServerInfo::make($data, '8.0');

        self::assertSame(45, $info->characterSet);
        self::assertSame(10, $info->protocolVersion);
        self::assertSame('8.0.28', $info->serverVersion);
    }
}
