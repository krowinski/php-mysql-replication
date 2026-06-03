<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event;

use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventConsumeShortPacketTest extends TestCase
{
    /**
     * consume() must skip any response shorter than the 20-byte minimum
     * (1 status byte + 19-byte event header). Such a response is a leftover OK
     * packet from a misaligned or un-drained auth handshake, not an event.
     * Without the guard it crashes with a TypeError on readInt32()/readUInt16()
     * (PHP 8 strict types); with it, the short packet is skipped and nothing is
     * dispatched.
     */
    #[DataProvider('shortPackets')]
    public function testConsumeSkipsResponsesShorterThanAnEventHeader(string $response): void
    {
        $socket = $this->createMock(BinLogSocketConnect::class);
        $socket->method('getResponse')->willReturn($response);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $event = new Event(
            $socket,
            $this->createMock(RowEventFactory::class),
            $dispatcher,
            $this->createMock(CacheInterface::class),
            $this->createMock(Config::class),
            $this->createMock(BinLogServerInfo::class)
        );

        $event->consume();
    }

    public static function shortPackets(): array
    {
        return [
            'empty response' => [''],
            'OK packet (7 bytes)' => ["\x00\x00\x00\x02\x00\x00\x00"],
            'one byte under the 20-byte header' => [str_repeat("\x00", 19)],
        ];
    }
}
