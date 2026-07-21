<?php

declare(strict_types=1);

namespace MySQLReplication\BinaryDataReader;

class BinaryDataReader
{
    public const NULL_COLUMN = 251;

    public const UNSIGNED_CHAR_COLUMN = 251;

    public const UNSIGNED_SHORT_COLUMN = 252;

    public const UNSIGNED_INT24_COLUMN = 253;

    public const UNSIGNED_INT64_COLUMN = 254;

    public const UNSIGNED_CHAR_LENGTH = 1;

    public const UNSIGNED_SHORT_LENGTH = 2;

    public const UNSIGNED_INT24_LENGTH = 3;

    public const UNSIGNED_INT32_LENGTH = 4;

    public const UNSIGNED_FLOAT_LENGTH = 4;

    public const UNSIGNED_DOUBLE_LENGTH = 8;

    public const UNSIGNED_INT40_LENGTH = 5;

    public const UNSIGNED_INT48_LENGTH = 6;

    public const UNSIGNED_INT56_LENGTH = 7;

    public const UNSIGNED_INT64_LENGTH = 8;

    private int $readBytes = 0;

    private int $offset = 0;

    private string $pushback = '';

    private bool $hasPushback = false;

    public function __construct(
        private readonly string $binaryData
    ) {
    }

    public static function pack64bit(int $value): string
    {
        return pack(
            'C8',
            ($value >> 0) & 0xFF,
            ($value >> 8) & 0xFF,
            ($value >> 16) & 0xFF,
            ($value >> 24) & 0xFF,
            ($value >> 32) & 0xFF,
            ($value >> 40) & 0xFF,
            ($value >> 48) & 0xFF,
            ($value >> 56) & 0xFF
        );
    }

    public function advance(int $length): void
    {
        $this->read($length);
    }

    public function readInt16(): int
    {
        return self::unpack('s', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
    }

    public function read(int $length): string
    {
        $this->readBytes += $length;

        // unread() is only ever used to push back a single byte that gets re-read immediately
        // (peeking a marker/sign byte), so this flag keeps the hot, pushback-free path free of
        // a strlen() call on every single field read.
        if (!$this->hasPushback) {
            $offset = $this->offset;
            $this->offset = $offset + $length;

            // Single-byte reads (readUInt8/readInt8/marker peeks/bitmap bytes) dominate call
            // volume — direct offset access skips the substr() call entirely for that case.
            return $length === 1 ? $this->binaryData[$offset] : substr($this->binaryData, $offset, $length);
        }

        $pushbackLength = strlen($this->pushback);
        if ($length <= $pushbackLength) {
            $result = substr($this->pushback, 0, $length);
            $this->pushback = substr($this->pushback, $length);
            $this->hasPushback = $this->pushback !== '';

            return $result;
        }

        $fromBinary = $length - $pushbackLength;
        $result = $this->pushback . substr($this->binaryData, $this->offset, $fromBinary);
        $this->pushback = '';
        $this->hasPushback = false;
        $this->offset += $fromBinary;

        return $result;
    }

    public function unread(string $data): void
    {
        $this->readBytes -= strlen($data);
        $this->pushback = $data . $this->pushback;
        $this->hasPushback = true;
    }

    public function readCodedBinary(): int|string|null
    {
        $c = ord($this->read(self::UNSIGNED_CHAR_LENGTH));
        if ($c === self::NULL_COLUMN) {
            return null;
        }
        if ($c < self::UNSIGNED_CHAR_COLUMN) {
            return $c;
        }
        if ($c === self::UNSIGNED_SHORT_COLUMN) {
            return $this->readUInt16();
        }
        if ($c === self::UNSIGNED_INT24_COLUMN) {
            return $this->readUInt24();
        }
        if ($c === self::UNSIGNED_INT64_COLUMN) {
            return $this->readUInt64AsIntOrString();
        }

        throw new BinaryDataReaderException('Column num ' . $c . ' not handled');
    }

    public function readUInt16(): int
    {
        return self::unpack('v', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
    }

    public function readUInt24(): int
    {
        $data = self::unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));

        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    /**
     * @return numeric-string
     */
    public function readUInt64(): string
    {
        return $this->unpackUInt64($this->read(self::UNSIGNED_INT64_LENGTH));
    }

    /**
     * @return numeric-string
     */
    public function unpackUInt64(string $binary): string
    {
        $data = self::unpack('V*', $binary);
        $high = (int)$data[2];
        // Value fits in 32 bits (the common case for ids/timestamps) — skip bcmath, it's slow and unneeded here.
        if ($high === 0) {
            return (string)(int)$data[1];
        }

        return bcadd((string)(int)$data[1], bcmul((string)$high, bcpow('2', '32')));
    }

    public function readInt24(): int
    {
        $data = self::unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000) {
            $res -= 0x1000000;
        }

        return $res;
    }

    public function readInt64(): string
    {
        $data = self::unpack('V*', $this->read(self::UNSIGNED_INT64_LENGTH));
        $high = (int)$data[2];
        // Value fits in 32 bits (the common case for ids/timestamps) — skip bcmath, it's slow and unneeded here.
        if ($high === 0) {
            return (string)(int)$data[1];
        }

        return bcadd((string)(int)$data[1], (string)($high << 32));
    }

    public function readLengthString(int $size): string
    {
        return $this->read((int) $this->readUIntBySize($size));
    }

    public function readUIntBySize(int $size): int|string
    {
        if ($size === self::UNSIGNED_CHAR_LENGTH) {
            return $this->readUInt8();
        }
        if ($size === self::UNSIGNED_SHORT_LENGTH) {
            return $this->readUInt16();
        }
        if ($size === self::UNSIGNED_INT24_LENGTH) {
            return $this->readUInt24();
        }
        if ($size === self::UNSIGNED_INT32_LENGTH) {
            return $this->readUInt32();
        }
        if ($size === self::UNSIGNED_INT40_LENGTH) {
            return $this->readUInt40();
        }
        if ($size === self::UNSIGNED_INT48_LENGTH) {
            return $this->readUInt48();
        }
        if ($size === self::UNSIGNED_INT56_LENGTH) {
            return $this->readUInt56();
        }
        if ($size === self::UNSIGNED_INT64_LENGTH) {
            return $this->readUInt64AsIntOrString();
        }

        throw new BinaryDataReaderException('$size ' . $size . ' not handled');
    }

    public function readUInt8(): int
    {
        return self::unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
    }

    public function readUInt32(): int
    {
        return self::unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
    }

    public function readUInt40(): int
    {
        $data1 = self::unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
        $data2 = self::unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];

        return $data1 + ($data2 << 8);
    }

    public function readUInt48(): int
    {
        $data = self::unpack('v3', $this->read(self::UNSIGNED_INT48_LENGTH));

        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    public function readUInt56(): int
    {
        $data1 = self::unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];
        $data2 = self::unpack('S', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];
        $data3 = self::unpack('I', $this->read(self::UNSIGNED_INT32_LENGTH))[1];

        return $data1 + ($data2 << 8) + ($data3 << 24);
    }

    public function readIntBeBySize(int $size): int
    {
        if ($size === self::UNSIGNED_CHAR_LENGTH) {
            return $this->readInt8();
        }
        if ($size === self::UNSIGNED_SHORT_LENGTH) {
            return $this->readInt16Be();
        }
        if ($size === self::UNSIGNED_INT24_LENGTH) {
            return $this->readInt24Be();
        }
        if ($size === self::UNSIGNED_INT32_LENGTH) {
            return $this->readInt32Be();
        }
        if ($size === self::UNSIGNED_INT40_LENGTH) {
            return $this->readInt40Be();
        }

        throw new BinaryDataReaderException('$size ' . $size . ' not handled');
    }

    public function readInt8(): int
    {
        $re = self::unpack('c', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];

        return $re >= 0x80 ? $re - 0x100 : $re;
    }

    public function readInt16Be(): int
    {
        $re = self::unpack('n', $this->read(self::UNSIGNED_SHORT_LENGTH))[1];

        return $re >= 0x8000 ? $re - 0x10000 : $re;
    }

    public function readInt24Be(): int
    {
        $data = self::unpack('C3', $this->read(self::UNSIGNED_INT24_LENGTH));
        $re = ($data[1] << 16) | ($data[2] << 8) | $data[3];

        return $re >= 0x800000 ? $re - 0x1000000 : $re;
    }

    public function readInt32Be(): int
    {
        $re = self::unpack('N', $this->read(self::UNSIGNED_INT32_LENGTH))[1];

        return $re >= 0x80000000 ? $re - 0x100000000 : $re;
    }

    public function readInt40Be(): int
    {
        $data1 = self::unpack('N', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
        $data2 = self::unpack('C', $this->read(self::UNSIGNED_CHAR_LENGTH))[1];

        return $data2 + ($data1 << 8);
    }

    public function readInt32(): int
    {
        return self::unpack('i', $this->read(self::UNSIGNED_INT32_LENGTH))[1];
    }

    public function readFloat(): float
    {
        return self::unpack('f', $this->read(self::UNSIGNED_FLOAT_LENGTH))[1];
    }

    public function readDouble(): float
    {
        return self::unpack('d', $this->read(self::UNSIGNED_DOUBLE_LENGTH))[1];
    }

    public function readTableId(): string
    {
        return $this->unpackUInt64($this->read(self::UNSIGNED_INT48_LENGTH) . chr(0) . chr(0));
    }

    public function isComplete(int $size): bool
    {
        return !($this->readBytes - 20 < $size);
    }

    public function getBinaryDataLength(): int
    {
        return strlen($this->pushback) + strlen($this->binaryData) - $this->offset;
    }

    public function getBinaryData(): string
    {
        return $this->pushback . substr($this->binaryData, $this->offset);
    }

    public function getBinarySlice(int $binary, int $start, int $size, int $binaryLength): int
    {
        $binary >>= $binaryLength - ($start + $size);
        $mask = ((1 << $size) - 1);

        return $binary & $mask;
    }

    public function getReadBytes(): int
    {
        return $this->readBytes;
    }

    public static function unpack(string $format, string $string): array
    {
        $unpacked = unpack($format, $string);
        if ($unpacked !== false) {
            return $unpacked;
        }
        return [];
    }

    public static function decodeNullLength(string $data, int &$offset = 0): string
    {
        $length = strpos($data, chr(0), $offset);
        if ($length === false) {
            return '';
        }

        $length -= $offset;
        $result = substr($data, $offset, $length);
        $offset += $length + 1;

        return $result;
    }

    private function readUInt64AsIntOrString(): int|string
    {
        $data = self::unpack('V*', $this->read(self::UNSIGNED_INT64_LENGTH));
        $high = (int)$data[2];
        // Value fits in 32 bits (the common case for ids/timestamps) — skip bcmath, it's slow and unneeded here.
        if ($high === 0) {
            return (int)$data[1];
        }

        $value = bcadd((string)(int)$data[1], bcmul((string)$high, bcpow('2', '32')));

        return bccomp($value, (string)PHP_INT_MAX) <= 0 ? (int)$value : $value;
    }
}
