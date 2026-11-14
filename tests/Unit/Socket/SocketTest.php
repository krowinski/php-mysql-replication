<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Socket;

use ErrorException;
use InvalidArgumentException;
use MySQLReplication\Socket\Socket;
use MySQLReplication\Socket\SocketException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Exercises the socket against real loopback peers. A guard alarm turns a read that would block forever
 * (the behaviour without a timeout) into a failure instead of a hung test run.
 */
class SocketTest extends TestCase
{
    private const GUARD_SECONDS = 8;

    /** @var resource|null */
    private $server;

    /** @var resource|null */
    private $peer;

    /** @var resource|null */
    private $process;

    /** @var array<int, resource> */
    private array $pipes = [];

    protected function setUp(): void
    {
        if (!function_exists('pcntl_alarm')) {
            self::markTestSkipped('pcntl is required for the guard alarm.');
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, static function (): void {
            throw new RuntimeException('Guard alarm: the call blocked past ' . self::GUARD_SECONDS . ' s.');
        }, false);
        pcntl_alarm(self::GUARD_SECONDS);
    }

    protected function tearDown(): void
    {
        pcntl_alarm(0);
        pcntl_signal(SIGALRM, SIG_DFL);
        pcntl_signal(SIGUSR1, SIG_DFL);

        if (is_resource($this->peer)) {
            fclose($this->peer);
        }
        if (is_resource($this->server)) {
            fclose($this->server);
        }
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }

    public function testShouldFailASilentConnectionWithinTheReadTimeout(): void
    {
        // The peer completes the TCP handshake (the kernel backlog does) but never sends or closes:
        // the shape of a connection dropped by a middlebox without FIN or RST.
        $port = $this->listen();
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        $started = hrtime(true);
        $exception = $this->readExpectingFailure($socket, 4);
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertSame(SocketException::SOCKET_READ_TIMEOUT_CODE, $exception->getCode());
        self::assertSame('No data from the replication source for 0.5 s (read 0 of 4 bytes).', $exception->getMessage());
        self::assertGreaterThanOrEqual(0.45, $elapsed);
        self::assertLessThan(1.5, $elapsed);
    }

    public function testShouldFailAPacketThatStopsPartWayWithinTwoTimeouts(): void
    {
        $port = $this->listen();
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);
        $this->peer = $this->accept();
        fwrite($this->peer, 'ab');

        $started = hrtime(true);
        $exception = $this->readExpectingFailure($socket, 4);
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertSame(SocketException::SOCKET_READ_TIMEOUT_CODE, $exception->getCode());
        self::assertSame('No data from the replication source for 0.5 s (read 2 of 4 bytes).', $exception->getMessage());
        self::assertLessThan(2.0, $elapsed);
    }

    public function testShouldReadAPacketThatKeepsArrivingForLongerThanTheTimeout(): void
    {
        // 12 bytes, one every 0.2 s: about 2.4 s for the packet, never 0.5 s without progress.
        $port = $this->spawnPeer('for ($i = 0; $i < 12; $i++) { fwrite($c, chr(65 + $i)); usleep(200000); } usleep(300000);');
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame('ABCDEFGHIJKL', $socket->readFromSocket(12));
    }

    public function testShouldReportAPeerThatClosesAfterPartOfAPacketAsADisconnect(): void
    {
        $port = $this->listen();
        $socket = new Socket(5.0);
        $socket->connectToStream('127.0.0.1', $port);
        $this->peer = $this->accept();
        fwrite($this->peer, 'ab');
        fclose($this->peer);

        $exception = $this->readExpectingFailure($socket, 4);

        self::assertSame(SocketException::SOCKET_DISCONNECTED_CODE, $exception->getCode());
    }

    public function testShouldReportACleanDisconnectAtOnce(): void
    {
        $port = $this->listen();
        $socket = new Socket(5.0);
        $socket->connectToStream('127.0.0.1', $port);
        fclose($this->accept());

        $started = hrtime(true);
        $exception = $this->readExpectingFailure($socket, 4);
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertSame(SocketException::SOCKET_DISCONNECTED_CODE, $exception->getCode());
        self::assertLessThan(1.0, $elapsed);
    }

    public function testShouldReportAResetConnectionAsASocketExceptionEvenWhenWarningsThrow(): void
    {
        // Mirrors a Laravel application: E_ALL reporting, and HandleExceptions::handleError turning a reported
        // warning into an ErrorException, which the reconnect path (catching SocketException) would never see.
        $previousReporting = error_reporting(E_ALL);
        set_error_handler(static function (int $level, string $message): bool {
            if ((error_reporting() & $level) === 0) {
                return false;
            }

            throw new ErrorException($message, 0, $level);
        });

        try {
            $port = $this->listen();
            $socket = new Socket(5.0);
            $socket->connectToStream('127.0.0.1', $port);
            // SO_LINGER 0 makes the close send RST instead of FIN.
            $stream = $this->accept();
            $peer = socket_import_stream($stream);
            self::assertNotFalse($peer);
            socket_set_option($peer, SOL_SOCKET, SO_LINGER, ['l_onoff' => 1, 'l_linger' => 0]);
            fclose($stream);

            $exception = $this->readExpectingFailure($socket, 4);
            self::assertSame(SOCKET_ECONNRESET, $exception->getCode());

            unset($socket);
        } finally {
            restore_error_handler();
            error_reporting($previousReporting);
        }
    }

    public function testShouldResumeAReadInterruptedByASignal(): void
    {
        // A signal whose handler returns (unlike SIGTERM's exit) must not cost the connection.
        $interrupted = false;
        pcntl_signal(SIGUSR1, static function () use (&$interrupted): void {
            $interrupted = true;
        }, false);
        $port = $this->spawnPeer('usleep(300000); posix_kill(' . getmypid() . ', SIGUSR1); usleep(300000); fwrite($c, "data"); usleep(300000);');
        $socket = new Socket(2.0);
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame('data', $socket->readFromSocket(4));
        self::assertTrue($interrupted);
    }

    public function testShouldNotLetARecurringSignalHoldASilentReadOpen(): void
    {
        // A handler that returns, called every 0.1 s for 3 s: each interrupt used to start a whole new timeout,
        // so the read only failed once the signals stopped.
        pcntl_signal(SIGUSR1, static function (): void {
        }, false);
        $port = $this->spawnPeer('for ($i = 0; $i < 30; $i++) { usleep(100000); posix_kill(' . getmypid() . ', SIGUSR1); } usleep(300000);');
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        $started = hrtime(true);
        $exception = $this->readExpectingFailure($socket, 4);
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertSame(SocketException::SOCKET_READ_TIMEOUT_CODE, $exception->getCode());
        self::assertSame('No data from the replication source for 0.5 s (read 0 of 4 bytes).', $exception->getMessage());
        self::assertGreaterThanOrEqual(0.45, $elapsed);
        self::assertLessThan(1.2, $elapsed);
    }

    public function testShouldNotLetARecurringSignalHoldAStalledWriteOpen(): void
    {
        pcntl_signal(SIGUSR1, static function (): void {
        }, false);
        // The peer never reads, so the send buffer fills and stays full while the signals keep coming.
        $port = $this->spawnPeer('for ($i = 0; $i < 40; $i++) { usleep(100000); posix_kill(' . getmypid() . ', SIGUSR1); } usleep(300000);');
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        $started = hrtime(true);
        try {
            $socket->writeToSocket(str_repeat('x', 64 * 1024 * 1024));
            self::fail('Expected a SocketException.');
        } catch (SocketException $exception) {
            self::assertSame(SocketException::SOCKET_WRITE_TIMEOUT_CODE, $exception->getCode());
        }
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertLessThan(2.0, $elapsed);
    }

    public function testShouldRestoreTheFullTimeoutWhenTheSocketIsUsedAgainAfterAnInterruptedTimeout(): void
    {
        // Signals every 0.1 s until 1.2 s and data at 1.5 s, timeout 1 s. The first read times out at about 1 s with
        // the receive timeout shortened by the last interrupt; the next read on the same socket must wait the full
        // second again and get the data, not fail within the leftover fraction.
        pcntl_signal(SIGUSR1, static function (): void {
        }, false);
        $port = $this->spawnPeer('for ($i = 0; $i < 12; $i++) { usleep(100000); posix_kill(' . getmypid() . ', SIGUSR1); } usleep(300000); fwrite($c, "data"); usleep(500000);');
        $socket = new Socket(1.0);
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame(SocketException::SOCKET_READ_TIMEOUT_CODE, $this->readExpectingFailure($socket, 4)->getCode());
        self::assertSame('data', $socket->readFromSocket(4));
    }

    public function testShouldCountTheIdleTimeFromTheLastByteOnceAPacketIsPartlyRead(): void
    {
        // "ab" at once, "c" at 1.3 s, then silence. The first call returns "ab" when its 1 s timeout ends; the rest
        // is read call by call, so the read fails 1 s after "c" arrived (about 2.3 s), not a whole timeout after
        // the call that brought "c" started (about 3 s).
        $port = $this->spawnPeer('fwrite($c, "ab"); usleep(1300000); fwrite($c, "c"); usleep(3000000);');
        $socket = new Socket(1.0);
        $socket->connectToStream('127.0.0.1', $port);

        $started = hrtime(true);
        $exception = $this->readExpectingFailure($socket, 4);
        $elapsed = (hrtime(true) - $started) / 1e9;

        self::assertSame('No data from the replication source for 1 s (read 3 of 4 bytes).', $exception->getMessage());
        self::assertGreaterThanOrEqual(2.1, $elapsed);
        self::assertLessThan(2.7, $elapsed);
    }

    public function testShouldReadTheEmptyPacketThatEndsAMaximumLengthPayload(): void
    {
        // A payload of exactly 16,777,215 bytes is followed by a packet header of length 0; the caller then reads
        // zero bytes, which must not touch the socket or fail.
        $port = $this->spawnPeer('fwrite($c, "\xff\xff\xff\x01"); $left = 16777215; while ($left > 0) { $left -= fwrite($c, str_repeat("y", min($left, 1048576))); } fwrite($c, "\x00\x00\x00\x02"); usleep(500000);');
        $socket = new Socket(2.0);
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame("\xff\xff\xff\x01", $socket->readFromSocket(4));
        self::assertSame(16777215, strlen($socket->readFromSocket(16777215)));
        self::assertSame("\x00\x00\x00\x02", $socket->readFromSocket(4));
        self::assertSame('', $socket->readFromSocket(0));
    }

    public function testShouldReturnAnEmptyStringForAZeroLengthReadWithoutATimeout(): void
    {
        $port = $this->listen();
        $socket = new Socket();
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame('', $socket->readFromSocket(0));
    }

    public function testShouldApplyTheTimeoutToEachReadSoAQuietButLiveStreamKeepsReading(): void
    {
        // A heartbeat-like peer: 4 bytes every 0.3 s for 6 rounds (about 1.8 s in total), well past
        // the 0.5 s timeout for the stream as a whole but inside it for every single read.
        $port = $this->spawnPeer('for ($i = 0; $i < 6; $i++) { usleep(300000); fwrite($c, "beat"); } usleep(200000);');
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        for ($i = 0; $i < 6; ++$i) {
            self::assertSame('beat', $socket->readFromSocket(4));
        }
    }

    public function testShouldReadAPacketThatArrivesInPieces(): void
    {
        $port = $this->spawnPeer('fwrite($c, "ab"); usleep(200000); fwrite($c, "cd"); usleep(200000);');
        $socket = new Socket(1.0);
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame('abcd', $socket->readFromSocket(4));
    }

    public function testShouldWriteEveryByteOfALargeCommandToASlowReader(): void
    {
        // 16 MiB drained at 256 KiB per 50 ms takes about 3 s, more than the socket buffers hold and far past
        // the 0.5 s timeout, so a send returns a short count after each timeout and the rest must be resumed.
        $port = $this->spawnPeer('$n = 0; while ($n < 16777216) { $d = stream_socket_recvfrom($c, 262144); if ($d === false || $d === "") { break; } $n += strlen($d); usleep(50000); } echo $n, "\n";');
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);

        $socket->writeToSocket(str_repeat('x', 16777216));

        self::assertSame('16777216', trim((string)fgets($this->pipes[1])));
    }

    public function testShouldFailAWriteThatMakesNoProgressWithinTheTimeout(): void
    {
        // The peer never reads, so the send buffer fills and stays full.
        $port = $this->listen();
        $socket = new Socket(0.5);
        $socket->connectToStream('127.0.0.1', $port);
        $this->peer = $this->accept();

        try {
            $socket->writeToSocket(str_repeat('x', 64 * 1024 * 1024));
            self::fail('Expected a SocketException.');
        } catch (SocketException $exception) {
            self::assertSame(SocketException::SOCKET_WRITE_TIMEOUT_CODE, $exception->getCode());
            self::assertMatchesRegularExpression('/^Wrote \d+ of 67108864 bytes before the write timeout of 0.5 s\.$/', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{0: float}>
     */
    public static function invalidTimeouts(): iterable
    {
        yield 'negative' => [-1.0];
        yield 'not a number' => [NAN];
        yield 'infinite' => [INF];
        yield 'beyond a timeval' => [3e9];
    }

    #[DataProvider('invalidTimeouts')]
    public function testShouldRejectAnInvalidTimeout(float $timeout): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Socket($timeout);
    }

    public function testShouldKeepNoTimeoutForASubclassThatDoesNotCallTheParentConstructor(): void
    {
        $port = $this->spawnPeer('fwrite($c, "data"); usleep(300000);');
        $socket = new class() extends Socket {
            public function __construct()
            {
            }
        };
        $socket->connectToStream('127.0.0.1', $port);

        self::assertSame('data', $socket->readFromSocket(4));
    }

    private function readExpectingFailure(Socket $socket, int $length): SocketException
    {
        try {
            $socket->readFromSocket($length);
        } catch (SocketException $exception) {
            return $exception;
        }

        self::fail('Expected a SocketException.');
    }

    private function listen(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        self::assertNotFalse($server, $error);
        $this->server = $server;

        $name = stream_socket_get_name($server, false);
        self::assertNotFalse($name);

        return (int)substr($name, strrpos($name, ':') + 1);
    }

    /**
     * @return resource
     */
    private function accept()
    {
        self::assertNotNull($this->server);
        $peer = stream_socket_accept($this->server, 1.0);
        self::assertNotFalse($peer);

        return $peer;
    }

    /**
     * Runs a one-connection peer in a child PHP process, so it can read or write while this process blocks.
     * The child prints its port, accepts one connection as $c, runs $body, then exits.
     */
    private function spawnPeer(string $body): int
    {
        $script = '$s = stream_socket_server("tcp://127.0.0.1:0"); echo explode(":", stream_socket_get_name($s, false))[1], "\n";'
            . ' $c = stream_socket_accept($s, 5); ' . $body;
        $process = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $this->process = $process;
        $this->pipes = $pipes;

        $port = (int)fgets($pipes[1]);
        self::assertGreaterThan(0, $port);

        return $port;
    }
}
