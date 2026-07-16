<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event;

use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * When semi-sync is negotiated, the master prefixes every binlog event packet
 * with a 2-byte header (0xef marker + ack-required flag) that must be
 * stripped before the event body is parsed, and an ACK must be sent back on
 * the socket whenever the flag requests it.
 */
class EventConsumeSemiSyncTest extends TestCase
{
    public function testSendsAckWhenSemiSyncRequestsIt(): void
    {
        $socket = $this->createMock(BinLogSocketConnect::class);
        $socket->method('getResponse')->willReturn($this->buildRawEventPacket(true, 1));
        $socket->method('getSemiSyncEnabled')->willReturn(true);
        $socket->method('getBinLogCurrent')->willReturn(new BinLogCurrent());
        $socket->method('getCheckSum')->willReturn(false);
        $socket->expects(self::once())->method('sendSemiSyncAck');

        $this->consume($socket);
    }

    public function testDoesNotAckWhenSemiSyncDoesNotRequestIt(): void
    {
        $socket = $this->createMock(BinLogSocketConnect::class);
        $socket->method('getResponse')->willReturn($this->buildRawEventPacket(true, 0));
        $socket->method('getSemiSyncEnabled')->willReturn(true);
        $socket->method('getBinLogCurrent')->willReturn(new BinLogCurrent());
        $socket->method('getCheckSum')->willReturn(false);
        $socket->expects(self::never())->method('sendSemiSyncAck');

        $this->consume($socket);
    }

    public function testDoesNotStripHeaderOrAckWhenSemiSyncIsDisabled(): void
    {
        $socket = $this->createMock(BinLogSocketConnect::class);
        $socket->method('getResponse')->willReturn($this->buildRawEventPacket(false));
        $socket->method('getSemiSyncEnabled')->willReturn(false);
        $socket->method('getBinLogCurrent')->willReturn(new BinLogCurrent());
        $socket->method('getCheckSum')->willReturn(false);
        $socket->expects(self::never())->method('sendSemiSyncAck');

        $this->consume($socket);
    }

    private function consume(BinLogSocketConnect $socket): void
    {
        $config = $this->createStub(Config::class);
        $config->method('checkEvent')->willReturn(false);

        $event = new Event(
            $socket,
            $this->createStub(RowEventFactory::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(CacheInterface::class),
            $config,
            $this->createStub(BinLogServerInfo::class)
        );

        $event->consume();
    }

    private function buildRawEventPacket(bool $withSemiSyncHeader, int $ackFlag = 0): string
    {
        // OK status byte
        $packet = chr(0);

        if ($withSemiSyncHeader) {
            $packet .= chr(0xef) . chr($ackFlag);
        }

        // 19-byte binlog event header, type = UNKNOWN_EVENT (0) so it's ignored
        // via Config::checkEvent() without needing a full valid event body.
        $packet .= pack('i', 0); // timestamp
        $packet .= pack('C', 0); // type
        $packet .= pack('i', 0); // server id
        $packet .= pack('i', 19); // size
        $packet .= pack('i', 0); // pos
        $packet .= pack('v', 0); // flag

        return $packet;
    }
}
