<?php
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\BinaryDataReader;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\Tests\Unit\BaseTest;

class BinaryDataReaderTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldRead(): void
    {
        $expected = 'zażółć gęślą jaźń';
        self::assertSame($expected, pack('H*', $this->getBinaryRead(unpack('H*', $expected)[1])->read(52)));
    }

    private function getBinaryRead($data): BinaryDataReader
    {
        return new BinaryDataReader($data);
    }

    /**
     * @test
     */
    public function shouldReadCodedBinary(): void
    {
        self::assertSame(0, $this->getBinaryRead(pack('C', ''))->readCodedBinary());
        self::assertNull($this->getBinaryRead(pack('C', BinaryDataReader::NULL_COLUMN))->readCodedBinary());
        self::assertSame(0, $this->getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_SHORT_COLUMN))->readCodedBinary());
        self::assertSame(0, $this->getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_INT24_COLUMN))->readCodedBinary());
    }

    /**
     * @test
     */
    public function shouldThrowErrorOnUnknownCodedBinary(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead(pack('i', 255))->readCodedBinary();
    }

    public function dataProviderForUInt(): array
    {
        return [
            [1, pack('c', 1), 1],
            [2, pack('v', 9999), 9999],
            [3, pack('CCC', 160, 190, 15), 1031840],
            [4, pack('I', 123123543), 123123543],
            [5, pack('CI', 71, 2570258120), 657986078791],
            [6, pack('v3', 2570258120, 2570258120, 2570258120), 7456176998088],
            [7, pack('CSI', 66, 7890, 2570258120), 43121775657013826]
        ];
    }

    /**
     * @test
     */
    public function shouldReadReadUInt64(): void
    {
        $this->assertSame('18374686483949813760', $this->getBinaryRead(pack('VV', 4278190080, 4278190080))->readUInt64());
    }

    /**
     * @dataProvider dataProviderForUInt
     * @test
     */
    public function shouldReadUIntBySize($size, $data, $expected): void
    {
        self::assertSame($expected, $this->getBinaryRead($data)->readUIntBySize($size));
    }

    /**
     * @test
     */
    public function shouldThrowErrorOnReadUIntBySizeNotSupported(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead('')->readUIntBySize(32);
    }

    public function dataProviderForBeInt(): array
    {
        return [
            [1, pack('c', 4), 4],
            [2, pack('n', 9999), 9999],
            [3, pack('CCC', 160, 190, 15), -6242801],
            [4, pack('i', 123123543), 1471632903],
            [5, pack('NC', 71, 2570258120), 18376],
        ];
    }

    /**
     * @dataProvider dataProviderForBeInt
     * @test
     */
    public function shouldReadIntBeBySize(int $size, string $data, int $expected): void
    {
        self::assertSame($expected, $this->getBinaryRead($data)->readIntBeBySize($size));
    }

    /**
     * @test
     */
    public function shouldThrowErrorOnReadIntBeBySizeNotSupported(): void
    {
        $this->expectException(BinaryDataReaderException::class);

        $this->getBinaryRead('')->readIntBeBySize(666);
    }

    /**
     * @test
     */
    public function shouldReadInt16(): void
    {
        $expected = 1000;
        self::assertSame($expected, $this->getBinaryRead(pack('s', $expected))->readInt16());
    }

    /**
     * @test
     */
    public function shouldUnreadAdvance(): void
    {
        $binaryDataReader = $this->getBinaryRead('123');

        self::assertEquals('123', $binaryDataReader->getData());
        self::assertEquals(0, $binaryDataReader->getReadBytes());

        $binaryDataReader->advance(2);

        self::assertEquals('3', $binaryDataReader->getData());
        self::assertEquals(2, $binaryDataReader->getReadBytes());

        $binaryDataReader->unread('12');

        self::assertEquals('123', $binaryDataReader->getData());
        self::assertEquals(0, $binaryDataReader->getReadBytes());
    }

    /**
     * @test
     */
    public function shouldReadInt24(): void
    {
        self::assertSame(-6513508, $this->getBinaryRead(pack('C3', -100, -100, -100))->readInt24());
    }

    /**
     * @test
     */
    public function shouldReadInt64(): void
    {
        self::assertSame('-72057589759737856', $this->getBinaryRead(pack('VV', 4278190080, 4278190080))->readInt64());
    }

    /**
     * @test
     */
    public function shouldReadLengthCodedPascalString(): void
    {
        $expected = 255;
        self::assertSame(
            $expected,
            hexdec(
                bin2hex(
                    $this->getBinaryRead(pack('cc', 1, $expected))->readLengthString(1)
                )
            )
        );
    }

    /**
     * @test
     */
    public function shouldReadInt32(): void
    {
        $expected = 777333;
        self::assertSame($expected, $this->getBinaryRead(pack('i', $expected))->readInt32());
    }


    /**
     * @test
     */
    public function shouldReadFloat(): void
    {
        $expected = 0.001;
        self::assertSame($expected, $this->getBinaryRead(pack('f', $expected))->readFloat());
    }

    /**
     * @test
     */
    public function shouldReadDouble(): void
    {
        $expected = 1321312312.143567586;
        self::assertSame($expected, $this->getBinaryRead(pack('d', $expected))->readDouble());
    }

    /**
     * @test
     */
    public function shouldReadTableId(): void
    {
        self::assertSame(
            '7456176998088',
            $this->getBinaryRead(pack('v3', 2570258120, 2570258120, 2570258120))->readTableId()
        );
    }

    /**
     * @test
     */
    public function shouldCheckIsCompleted(): void
    {
        self::assertFalse($this->getBinaryRead('')->isComplete(1));

        $r = $this->getBinaryRead(str_repeat('-', 30));
        $r->advance(21);
        self::assertTrue($r->isComplete(1));
    }

    /**
     * @test
     */
    public function shouldPack64bit(): void
    {
        $expected = 9223372036854775807;
        self::assertSame((string)$expected, $this->getBinaryRead(BinaryDataReader::pack64bit($expected))->readInt64());
    }

    /**
     * @test
     */
    public function shouldGetBinaryDataLength(): void
    {
        self::assertSame(3, $this->getBinaryRead('foo')->getBinaryDataLength());
    }
}