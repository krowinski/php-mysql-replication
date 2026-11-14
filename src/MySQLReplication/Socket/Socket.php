<?php

declare(strict_types=1);

namespace MySQLReplication\Socket;

use InvalidArgumentException;
use Socket as NativeSocket;

class Socket implements SocketInterface
{
    /** The largest timeout a timeval's seconds field holds on every platform. */
    private const MAX_TIMEOUT_SECONDS = 2147483647.0;

    private NativeSocket $socket;

    /**
     * Declared with a default rather than promoted, so a subclass with its own constructor keeps the
     * previous unlimited behaviour.
     */
    private float $timeoutSeconds = 0.0;

    /** Whether a signal left SO_RCVTIMEO shorter than the configured timeout; restored before the next wait. */
    private bool $receiveTimeoutShortened = false;

    /** The same for SO_SNDTIMEO. */
    private bool $sendTimeoutShortened = false;

    /**
     * @param float $timeoutSeconds How long connect, a read or a write may wait without any progress; 0 waits
     *                              forever. A replication source sends an event or a heartbeat at least once
     *                              per heartbeat period, so a timeout of several periods turns a silently
     *                              dropped connection into a SocketException. A read fails after one timeout
     *                              without any new byte; a packet already partly received gets at most one
     *                              more timeout before it fails (so at most two timeouts after its last byte).
     *                              Signals that interrupt a call do not extend the timeout.
     */
    public function __construct(float $timeoutSeconds = 0.0)
    {
        if (!is_finite($timeoutSeconds) || $timeoutSeconds < 0.0 || $timeoutSeconds > self::MAX_TIMEOUT_SECONDS) {
            throw new InvalidArgumentException('The socket timeout must be 0 (disabled) or a finite positive number of seconds.');
        }

        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function __destruct()
    {
        if (!isset($this->socket)) {
            return;
        }

        // A dead connection cannot shut down cleanly; that is not an error worth a warning here.
        @socket_shutdown($this->socket);
        socket_close($this->socket);
    }

    public function connectToStream(string $host, int $port): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new SocketException(SocketException::SOCKET_UNABLE_TO_CREATE_MESSAGE . socket_strerror(socket_last_error()), SocketException::SOCKET_UNABLE_TO_CREATE_CODE);
        }
        $this->socket = $socket;
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);

        if ($this->timeoutSeconds > 0.0) {
            // SO_RCVTIMEO bounds each blocking recv; SO_SNDTIMEO bounds each write and, on Linux, the blocking connect.
            $this->setTimeout(SO_RCVTIMEO, $this->timeoutSeconds);
            $this->setTimeout(SO_SNDTIMEO, $this->timeoutSeconds);
        }

        // Failures are reported as a SocketException, which callers treat as retryable. Without the silence
        // operator PHP also raises a warning, which a framework error handler (Laravel's) turns into an
        // ErrorException that bypasses the reconnect path. The same applies to every socket call below.
        if (!@socket_connect($this->socket, $host, $port)) {
            $code = $this->getSocketErrorCode();
            if ($this->isTimeout($code) || $code === SOCKET_EINPROGRESS) {
                throw new SocketException(sprintf(SocketException::SOCKET_CONNECT_TIMEOUT_MESSAGE, $this->timeoutSeconds, $code), SocketException::SOCKET_CONNECT_TIMEOUT_CODE);
            }

            throw new SocketException($this->getSocketErrorMessage(), $code);
        }
    }

    public function readFromSocket(int $length): string
    {
        // A payload of exactly 16,777,215 bytes (or a multiple) ends with an empty packet. socket_recv() refuses a
        // zero length with false and no error code, which used to surface as SocketException("Success", 0) and a
        // replay of the same event on every reconnect.
        if ($length === 0) {
            return '';
        }

        // A read that failed after a signal may have left a shortened timeout behind.
        $this->restoreTimeout(SO_RCVTIMEO);

        $started = hrtime(true);
        $received = @socket_recv($this->socket, $buf, $length, MSG_WAITALL);
        if ($length === $received) {
            return $buf;
        }

        $progressed = is_int($received) && $received > 0;

        // Bytes may have arrived at any point of the first call, so the idle time counts from now; a call that
        // brought nothing (a signal) counts from its start.
        return $this->readRemainder($length, $received, $progressed ? $buf : '', $progressed ? hrtime(true) : $started);
    }

    public function writeToSocket(string $data): void
    {
        $length = strlen($data);
        $written = 0;
        $this->restoreTimeout(SO_SNDTIMEO);
        $idleSince = hrtime(true);

        while ($written < $length) {
            $sent = @socket_write($this->socket, $written === 0 ? $data : substr($data, $written), $length - $written);

            if ($sent === false) {
                $code = $this->getSocketErrorCode();
                if ($code === SOCKET_EINTR && $this->shortenTimeoutAfterInterrupt(SO_SNDTIMEO, $idleSince)) {
                    continue;
                }
                if ($code === SOCKET_EINTR || $this->isTimeout($code)) {
                    throw new SocketException(sprintf(SocketException::SOCKET_WRITE_TIMEOUT_MESSAGE, $written, $length, $this->timeoutSeconds), SocketException::SOCKET_WRITE_TIMEOUT_CODE);
                }

                throw new SocketException(SocketException::SOCKET_UNABLE_TO_WRITE_MESSAGE . $this->getSocketErrorMessage(), SocketException::SOCKET_UNABLE_TO_WRITE_CODE);
            }

            if ($sent === 0) {
                throw new SocketException(SocketException::SOCKET_UNABLE_TO_WRITE_MESSAGE . 'no bytes written', SocketException::SOCKET_UNABLE_TO_WRITE_CODE);
            }

            $written += $sent;
            $idleSince = hrtime(true);
            $this->restoreTimeout(SO_SNDTIMEO);
        }
    }

    /**
     * Finishes a read whose first MSG_WAITALL call came back short. A short count comes from the read timeout
     * after partial data, a signal, or the peer closing; each further call waits at most one timeout for the
     * next bytes, so a packet that keeps arriving, however slowly, is read whole, and a connection that stops
     * delivering fails within one timeout.
     */
    private function readRemainder(int $length, int|false $received, string $data, int|float $idleSince): string
    {
        while (true) {
            if ($received === 0) {
                // http://php.net/manual/en/function.socket-recv.php#47182
                throw new SocketException(SocketException::SOCKET_DISCONNECTED_MESSAGE, SocketException::SOCKET_DISCONNECTED_CODE);
            }

            if ($received === false) {
                $code = $this->getSocketErrorCode();
                $interrupted = $code === SOCKET_EINTR;
                if (!$interrupted || !$this->shortenTimeoutAfterInterrupt(SO_RCVTIMEO, $idleSince)) {
                    if ($interrupted || $this->isTimeout($code)) {
                        throw new SocketException(sprintf(SocketException::SOCKET_READ_TIMEOUT_MESSAGE, $this->timeoutSeconds, strlen($data), $length), SocketException::SOCKET_READ_TIMEOUT_CODE);
                    }

                    throw new SocketException($this->getSocketErrorMessage(), $code);
                }
            }

            if (strlen($data) === $length) {
                return $data;
            }

            // Without MSG_WAITALL each call returns as soon as any bytes arrive, so the timeout measures
            // time without progress rather than time to complete the packet.
            $received = @socket_recv($this->socket, $chunk, $length - strlen($data), 0);
            if (is_int($received) && $received > 0) {
                $data .= $chunk;
                $idleSince = hrtime(true);
                $this->restoreTimeout(SO_RCVTIMEO);
            }
        }
    }

    /**
     * A signal interrupts a timed call with EINTR, and the next call would start a whole new timeout, so a
     * handled signal that keeps recurring could hold a dead connection open forever. After an interrupt the
     * next call gets only what is left of the timeout since the last progress.
     *
     * @param int|float $idleSince hrtime(true) at the last progress
     *
     * @return bool false when the timeout since the last progress has run out
     */
    private function shortenTimeoutAfterInterrupt(int $option, int|float $idleSince): bool
    {
        if ($this->timeoutSeconds <= 0.0) {
            return true;
        }

        $remaining = $this->timeoutSeconds - (hrtime(true) - $idleSince) / 1e9;
        if ($remaining <= 0.0) {
            return false;
        }

        $this->setTimeout($option, $remaining);
        if ($option === SO_RCVTIMEO) {
            $this->receiveTimeoutShortened = true;
        } else {
            $this->sendTimeoutShortened = true;
        }

        return true;
    }

    /**
     * Puts back the configured timeout after progress, or before the next call when a failed one left it short.
     */
    private function restoreTimeout(int $option): void
    {
        if ($option === SO_RCVTIMEO ? !$this->receiveTimeoutShortened : !$this->sendTimeoutShortened) {
            return;
        }

        $this->setTimeout($option, $this->timeoutSeconds);
        if ($option === SO_RCVTIMEO) {
            $this->receiveTimeoutShortened = false;
        } else {
            $this->sendTimeoutShortened = false;
        }
    }

    private function setTimeout(int $option, float $seconds): void
    {
        // Silenced like the calls below: a refused option must reach the caller as code 1004, not as a warning
        // that a framework error handler turns into an ErrorException.
        if (!@socket_set_option($this->socket, SOL_SOCKET, $option, $this->timeval($seconds))) {
            throw new SocketException(SocketException::SOCKET_UNABLE_TO_SET_TIMEOUT_MESSAGE . $this->getSocketErrorMessage(), SocketException::SOCKET_UNABLE_TO_SET_TIMEOUT_CODE);
        }
    }

    private function isTimeout(int $code): bool
    {
        return $this->timeoutSeconds > 0.0 && in_array($code, [SOCKET_EAGAIN, SOCKET_EWOULDBLOCK], true);
    }

    /**
     * @return array{sec: int, usec: int}
     */
    private function timeval(float $timeoutSeconds): array
    {
        $seconds = (int)floor($timeoutSeconds);
        $microseconds = (int)round(($timeoutSeconds - $seconds) * 1_000_000);

        if ($microseconds >= 1_000_000) {
            ++$seconds;
            $microseconds -= 1_000_000;
        }

        // An all-zero timeval would mean "wait forever".
        if ($seconds === 0 && $microseconds === 0) {
            $microseconds = 1;
        }

        return [
            'sec' => $seconds,
            'usec' => $microseconds,
        ];
    }

    private function getSocketErrorMessage(): string
    {
        return socket_strerror($this->getSocketErrorCode());
    }

    private function getSocketErrorCode(): int
    {
        return isset($this->socket) ? socket_last_error($this->socket) : socket_last_error();
    }
}
