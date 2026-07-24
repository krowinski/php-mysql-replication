<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\BinaryDataReader;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BinaryDataReaderTest extends TestCase
{
    public function testShouldRead(): void
    {
        $expected = 'zażółć gęślą jaźń';
        self::assertSame($expected, pack('H*', $this->getBinaryRead(unpack('H*', $expected)[1])->read(52)));
    }

    public function testShouldReadCodedBinary(): void
    {
        self::assertSame(0, $this->getBinaryRead(pack('C', ''))->readCodedBinary());
        self::assertNull($this->getBinaryRead(pack('C', BinaryDataReader::NULL_COLUMN))->readCodedBinary());
        self::assertSame(0, $this->getBinaryRead(pack('V', BinaryDataReader::UNSIGNED_SHORT_COLUMN))->readCodedBinary());
        self::assertSame(0, $this->getBinaryRead(pack('V', BinaryDataReader::UNSIGNED_INT24_COLUMN))->readCodedBinary());
    }

    public function testShouldThrowErrorOnUnknownCodedBinary(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead(pack('V', 255))
            ->readCodedBinary();
    }

    public static function dataProviderForCodedBinaryUnsignedInt64(): array
    {
        return [
            'regular value' => [pack('C8', 1, 2, 3, 4, 5, 6, 7, 8), 578437695752307201],
            // 0xFFFFFFFFFFFFFFFF does not fit a 64-bit signed PHP int, so it comes back as a numeric string
            'max uint64 value' => [pack('C8', 255, 255, 255, 255, 255, 255, 255, 255), '18446744073709551615'],
            // -1's two's complement bit pattern is identical to the unsigned max, so it must decode the same way
            '-1 as unsigned' => [BinaryDataReader::pack64bit(-1), '18446744073709551615'],
        ];
    }

    #[DataProvider('dataProviderForCodedBinaryUnsignedInt64')]
    public function testShouldReadCodedBinaryForUnsignedInt64Column(string $data, int|string $expected): void
    {
        // An 8-byte length must follow 0xFE (UNSIGNED_INT64_COLUMN) header byte
        self::assertSame($expected, $this->getBinaryRead(pack('C', BinaryDataReader::UNSIGNED_INT64_COLUMN) . $data)->readCodedBinary());
    }

    public static function dataProviderForUInt(): array
    {
        return [
            [1, pack('c', 1), 1],
            [2, pack('v', 9999), 9999],
            [3, pack('CCC', 160, 190, 15), 1031840],
            [4, pack('V', 123123543), 123123543],
            [5, pack('CV', 71, 2570258120), 657986078791],
            [6, pack('v3', 2570258120, 2570258120, 2570258120), 7456176998088],
            [7, pack('CvV', 66, 7890, 2570258120), 43121775657013826],
            [8, pack('C8', 1, 2, 3, 4, 5, 6, 7, 8), 578437695752307201],
        ];
    }

    public function testShouldReadReadUInt64(): void
    {
        $this->assertSame('18374686483949813760', $this->getBinaryRead(pack('VV', 4278190080, 4278190080)) ->readUInt64());
    }

    #[DataProvider('dataProviderForUInt')]
    public function testShouldReadUIntBySize(mixed $size, mixed $data, mixed $expected): void
    {
        self::assertSame($expected, $this->getBinaryRead($data)->readUIntBySize($size));
    }

    public function testShouldReadUIntBySizeAtMaxUint64WithoutCrashing(): void
    {
        self::assertSame('18446744073709551615', $this->getBinaryRead(pack('C8', 255, 255, 255, 255, 255, 255, 255, 255)) ->readUIntBySize(BinaryDataReader::UNSIGNED_INT64_LENGTH));
    }

    public function testShouldThrowErrorOnReadUIntBySizeNotSupported(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead('')
            ->readUIntBySize(32);
    }

    public static function dataProviderForBeInt(): array
    {
        return [[1, pack('c', 4), 4], [2, pack('n', 9999), 9999], [3, pack('CCC', 160, 190, 15), -6242801], [4, pack('N', 123123543), 123123543], [5, pack('NC', 71, 2570258120), 18376]];
    }

    #[DataProvider('dataProviderForBeInt')] public function testShouldReadIntBeBySize(int $size, string $data, int $expected): void
    {
        self::assertSame($expected, $this->getBinaryRead($data)->readIntBeBySize($size));
    }

    public function testShouldThrowErrorOnReadIntBeBySizeNotSupported(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead('')
            ->readIntBeBySize(666);
    }

    public function testShouldReadInt16(): void
    {
        $expected = 1000;
        self::assertSame($expected, $this->getBinaryRead(pack('v', $expected))->readInt16());
    }

    public function testShouldUnreadAdvance(): void
    {
        $binaryDataReader = $this->getBinaryRead('123');

        self::assertEquals('123', $binaryDataReader->getBinaryData());
        self::assertEquals(0, $binaryDataReader->getReadBytes());

        $binaryDataReader->advance(2);

        self::assertEquals('3', $binaryDataReader->getBinaryData());
        self::assertEquals(2, $binaryDataReader->getReadBytes());

        $binaryDataReader->unread('12');

        self::assertEquals('123', $binaryDataReader->getBinaryData());
        self::assertEquals(0, $binaryDataReader->getReadBytes());
    }

    public function testShouldReadInt24(): void
    {
        self::assertSame(-6513508, $this->getBinaryRead(pack('C3', -100, -100, -100))->readInt24());
    }

    public function testShouldReadInt64(): void
    {
        self::assertSame('-72057589759737856', $this->getBinaryRead(pack('VV', 4278190080, 4278190080))->readInt64());
    }

    public function testShouldReadLengthCodedPascalString(): void
    {
        $expected = 255;
        self::assertSame($expected, hexdec(bin2hex($this->getBinaryRead(pack('cc', 1, $expected))->readLengthString(1))));
    }

    public function testShouldReadInt32(): void
    {
        $expected = 777333;
        self::assertSame($expected, $this->getBinaryRead(pack('V', $expected))->readInt32());
    }

    public function testShouldReadFloat(): void
    {
        $expected = 0.001;
        // we need to add round as php have problem with precision in floats
        self::assertSame($expected, round($this->getBinaryRead(pack('g', $expected))->readFloat(), 3));
    }

    public function testShouldReadDouble(): void
    {
        $expected = 1321312312.143567586;
        self::assertSame($expected, $this->getBinaryRead(pack('e', $expected))->readDouble());
    }

    public function testShouldReadTableId(): void
    {
        self::assertSame('7456176998088', $this->getBinaryRead(pack('v3', 2570258120, 2570258120, 2570258120)) ->readTableId());
    }

    public function testShouldCheckIsCompleted(): void
    {
        self::assertFalse($this->getBinaryRead('')->isComplete(1));

        $r = $this->getBinaryRead(str_repeat('-', 30));
        $r->advance(21);
        self::assertTrue($r->isComplete(1));
    }

    public function testShouldPack64bit(): void
    {
        $expected = 9223372036854775807;
        self::assertSame((string)$expected, $this->getBinaryRead(BinaryDataReader::pack64bit($expected))->readInt64());
    }

    public function testShouldGetBinaryDataLength(): void
    {
        self::assertSame(3, $this->getBinaryRead('foo')->getBinaryDataLength());
    }

    private function getBinaryRead(string $data): BinaryDataReader
    {
        return new BinaryDataReader($data);
    }

    // The fixtures below are hand-built little-endian byte strings (never via pack('s'/'i'/'f'/'d'),
    // which are native-byte-order and would silently mask the same bug on a little-endian host).
    // Byte patterns are asymmetric so a big-endian misread produces a different, wrong value.

    public function testShouldReadInt16LittleEndian(): void
    {
        self::assertSame(-32767, $this->getBinaryRead("\x01\x80")->readInt16());
    }

    public function testShouldReadInt32LittleEndian(): void
    {
        self::assertSame(-2147483647, $this->getBinaryRead("\x01\x00\x00\x80")->readInt32());
    }

    public function testShouldReadUInt32LittleEndian(): void
    {
        self::assertSame(2147483649, $this->getBinaryRead("\x01\x00\x00\x80")->readUInt32());
    }

    public function testShouldReadUInt40LittleEndian(): void
    {
        self::assertSame(591292919723, $this->getBinaryRead("\xAB\xEF\xCD\xAB\x89")->readUInt40());
    }

    public function testShouldReadUInt56LittleEndian(): void
    {
        self::assertSame(38750972784150017, $this->getBinaryRead("\x01\x06\x80\xEF\xCD\xAB\x89")->readUInt56());
    }

    public function testShouldReadFloatLittleEndian(): void
    {
        self::assertSame(-1.5, $this->getBinaryRead("\x00\x00\xC0\xBF")->readFloat());
    }

    public function testShouldReadDoubleLittleEndian(): void
    {
        self::assertSame(-1.5, $this->getBinaryRead("\x00\x00\x00\x00\x00\x00\xF8\xBF")->readDouble());
    }
}
