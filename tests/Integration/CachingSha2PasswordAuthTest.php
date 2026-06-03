<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\Event\DTO\QueryDTO;

class CachingSha2PasswordAuthTest extends BaseCase
{
    /**
     * Regression guard for the caching_sha2_password auth-switch drain.
     *
     * On MySQL 8+, root authenticates with caching_sha2_password. With its
     * password already in the server auth cache (the test harness logs in as
     * root before the suite runs), the binlog handshake takes the fast-auth
     * path: the server sends an AuthMoreData packet (status byte 0x01) carrying
     * fast_auth_success (0x03), then the OK packet. switchAuth() must drain both
     * packets; without that drain every later getResponse() is offset by two
     * packets and the first read crashes in Event::consume(). BaseCase::setUp()
     * already completes that handshake, so reaching this test proves the drain
     * worked; we read one more event to confirm the stream is still aligned.
     */
    public function testBinlogHandshakeOverCachingSha2Password(): void
    {
        if ($this->checkForVersion(8.0)) {
            self::markTestSkipped('caching_sha2_password and switchAuth() apply to MySQL 8+ only');
        }

        self::assertGreaterThanOrEqual(
            8.0,
            $this->mySQLReplicationFactory->getServerInfo()->versionRevision
        );

        $this->connection->executeStatement('CREATE TABLE caching_sha2_handshake_check (id INT)');
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
    }
}
