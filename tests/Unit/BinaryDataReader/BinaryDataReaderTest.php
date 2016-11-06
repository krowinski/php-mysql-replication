<?php

namespace BinaryDataReader\Unit;

use Unit\BaseTest;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class BinaryDataReaderTest
 * @package BinaryDataReader\Unit
 * @covers MySQLReplication\BinaryDataReader\BinaryDataReader
 */
class BinaryDataReaderTest extends BaseTest
{
    /**
     * @param string $data
     * @return BinaryDataReader
     */
    private function getBinaryRead($data)
    {
        return new BinaryDataReader($data);
    }

    /**
     * @test
     */
    public function shouldRead()
    {
        $expected = 'zażółć gęślą jaźń';
        self::assertSame($expected, pack('H*', self::getBinaryRead(unpack('H*', $expected)[1])->read(52)));
    }

    /**
     * @test
     */
    public function shouldReadCodedBinary()
    {
        self::getBinaryRead(pack('C', ''))->readCodedBinary();
        self::getBinaryRead(pack('C', BinaryDataReader::NULL_COLUMN))->readCodedBinary();
        self::getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_SHORT_COLUMN))->readCodedBinary();
        self::getBinaryRead(pack('i', BinaryDataReader::UNSIGNED_INT24_COLUMN))->readCodedBinary();
        self::getBinaryRead(pack('V', BinaryDataReader::UNSIGNED_INT64_COLUMN) . pack('V', 2147483647) . pack('V', 2147483647))->readCodedBinary();
    }

    /**
     * @test
     * @expectedException \MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException
     */
    public function shouldThrowErrorOnUnknownCodedBinary()
    {
        self::getBinaryRead(pack('i', 255))->readCodedBinary();
    }

    public function dataProviderForUInt()
    {
        return [
          [ 1, pack('c', 1), 1 ],
          [ 2, pack('v', 9999), 9999 ],
          [ 3, pack('CCC', 160, 190, 15),  1031840],
          [ 4, pack('I', 123123543),  123123543],
          [ 5, pack('CI', 71, 2570258120), 657986078791 ],
          [ 6, pack('v3', 2570258120, 2570258120, 2570258120), 7456176998088 ],
          [ 7, pack('CSI', 66, 7890, 2570258120), 43121775657013826 ],
          [ 8, pack('VV', 4278190080, 4278190080), '18374686483949813760' ],
        ];
    }

    /**
     * @dataProvider dataProviderForUInt
     * @test
     * @param $size
     * @param $data
     * @param $expected
     * @throws \MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException
     */
    public function shouldReadUIntBySize($size, $data, $expected)
    {
       self::assertSame($expected, self::getBinaryRead($data)->readUIntBySize($size));
    }

    /**
     * @test
     * @expectedException \MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException
     */
    public function shouldThrowErrorOnReadUIntBySizeNotSupported()
    {
        self::getBinaryRead('')->readUIntBySize(32);
    }

    public function dataProviderForBeInt()
    {
        return [
            [ 1, pack('c', 4), 4 ],
            [ 2, pack('n', 9999), 9999 ],
            [ 3, pack('CCC', 160, 190, 15), -6242801],
            [ 4, pack('i', 123123543),  1471632903],
            [ 5, pack('NC', 71, 2570258120), 18376 ],
        ];
    }

    /**
     * @dataProvider dataProviderForBeInt
     * @test
     * @param $size
     * @param $data
     * @param $expected
     * @throws \MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException
     */
    public function shouldReadIntBeBySize($size, $data, $expected)
    {
        self::assertSame($expected, self::getBinaryRead($data)->readIntBeBySize($size));
    }

    /**
     * @test
     * @expectedException \MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException
     */
    public function shouldThrowErrorOnReadIntBeBySizeNotSupported()
    {
       self::getBinaryRead('')->readIntBeBySize(666);
    }

    /**
     * @test
     */
    public function shouldReadInt16()
    {
        $expected = 1000;
        self::assertSame($expected, self::getBinaryRead(pack('s', $expected))->readInt16());
    }

    /**
     * @test
     */
    public function shouldUnreadAdvance()
    {
        $binaryDataReader = self::getBinaryRead('123');

        self::assertAttributeEquals('123', 'binaryData', $binaryDataReader);
        self::assertAttributeEquals(0, 'readBytes', $binaryDataReader);

        $binaryDataReader->advance(2);

        self::assertAttributeEquals('3', 'binaryData', $binaryDataReader);
        self::assertAttributeEquals(2, 'readBytes', $binaryDataReader);

        $binaryDataReader->unread('12');

        self::assertAttributeEquals('123', 'binaryData', $binaryDataReader);
        self::assertAttributeEquals(0, 'readBytes', $binaryDataReader);
    }

    /**
     * @test
     */
    public function shouldReadInt24()
    {
        self::assertSame(-6513508, self::getBinaryRead(pack('C3', -100, -100, -100))->readInt24());
    }

    /**
     * @test
     */
    public function shouldReadInt64()
    {
        self::assertSame('-72057589759737856', self::getBinaryRead(pack('VV', 4278190080, 4278190080))->readInt64());
    }

    /**
     * @test
     */
    public function shouldReadLengthCodedPascalString()
    {
        $expected = 255;
        self::assertSame($expected, hexdec(bin2hex(self::getBinaryRead(pack('cc', 1, $expected))->readLengthCodedPascalString(1))));
    }

    /**
     * @test
     */
    public function shouldReadInt32()
    {
        $expected = 777333;
        self::assertSame($expected, self::getBinaryRead(pack('i', $expected))->readInt32());
    }


    /**
     * @test
     */
    public function shouldReadFloat()
    {
        $expected = 0.001;
        self::assertSame($expected, self::getBinaryRead(pack('f', $expected))->readFloat());
    }

    /**
     * @test
     */
    public function shouldReadDouble()
    {
        $expected = 1321312312.143567586;
        self::assertSame($expected, self::getBinaryRead(pack('d', $expected))->readDouble());
    }

    /**
     * @test
     */
    public function shouldReadTableId()
    {
        self::assertSame('7456176998088', self::getBinaryRead(pack('v3', 2570258120, 2570258120, 2570258120))->readTableId());
    }

    /**
     * @test
     */
    public function shouldCheckIsCompleted()
    {
        self::assertFalse(self::getBinaryRead('')->isComplete(1));

        $r =  self::getBinaryRead(str_repeat('-', 30));
        $r->advance(20);
        self::assertTrue($r->isComplete(1));
    }

    /**
     * @test
     */
    public function shouldPack64bit()
    {
        $expected = '9223372036854775807';
        self::assertSame($expected, self::getBinaryRead(BinaryDataReader::pack64bit($expected))->readInt64());
    }
}