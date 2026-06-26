<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\JsonBinaryDecoder;

use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderService;
use PHPUnit\Framework\TestCase;

class JsonBinaryDecoderServiceTest extends TestCase
{
    public function testShouldReturnNullForEmptyBinary(): void
    {
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder('');
        self::assertSame('null', $service->parseToString());
    }

    public function testShouldParseLiteralTrue(): void
    {
        // type=LITERAL(4) + literal=TRUE(1) + padding
        $binary = pack('CCC', JsonBinaryDecoderService::LITERAL, JsonBinaryDecoderService::LITERAL_TRUE, 0);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('true', $service->parseToString());
    }

    public function testShouldParseLiteralFalse(): void
    {
        // type=LITERAL(4) + literal=FALSE(2) + padding
        $binary = pack('CCC', JsonBinaryDecoderService::LITERAL, JsonBinaryDecoderService::LITERAL_FALSE, 0);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('false', $service->parseToString());
    }

    public function testShouldParseInt16Scalar(): void
    {
        // type=INT16(5) + int16 value 42
        $binary = pack('C', JsonBinaryDecoderService::INT16) . pack('s', 42);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('"42"', $service->parseToString());
    }

    public function testShouldParseUInt16Scalar(): void
    {
        // type=UINT16(6) + uint16 value 1000
        $binary = pack('C', JsonBinaryDecoderService::UINT16) . pack('v', 1000);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('"1000"', $service->parseToString());
    }

    public function testShouldParseDouble(): void
    {
        // type=DOUBLE(11) + double value
        $binary = pack('C', JsonBinaryDecoderService::DOUBLE) . pack('d', 3.14);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        $result = $service->parseToString();
        self::assertStringContainsString('3.14', $result);
    }

    public function testShouldThrowExceptionForUnknownType(): void
    {
        $this->expectException(JsonBinaryDecoderException::class);
        $this->expectExceptionCode(JsonBinaryDecoderException::UNKNOWN_JSON_TYPE_CODE);

        // type=99 (unknown)
        $binary = pack('C', 99);
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        $service->parseToString();
    }

    public function testShouldParseSimpleJsonObject(): void
    {
        // {"a":"b"} in MySQL JSON binary format (SMALL_OBJECT)
        $binary = pack(
            'C*',
            0x00, // SMALL_OBJECT
            0x01,
            0x00, // elem count = 1
            0x0e,
            0x00, // total bytes = 14
            0x0b,
            0x00, // key offset = 11
            0x01,
            0x00, // key length = 1
            0x0c,       // value type = STRING(12)
            0x0c,
            0x00, // value offset = 12
            0x61,       // key = 'a'
            0x01,       // varlen = 1
            0x62        // value = 'b'
        );
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('{"a":"b"}', $service->parseToString());
    }

    public function testShouldParseSimpleJsonArray(): void
    {
        // [true] in MySQL JSON binary format (SMALL_ARRAY)
        // type=SMALL_ARRAY(2), elem=1, bytes=7
        // value entry: type=LITERAL(4), inline literal=TRUE(1)+padding(0)
        $binary = pack(
            'C*',
            0x02,       // SMALL_ARRAY
            0x01,
            0x00, // elem count = 1
            0x07,
            0x00, // total bytes = 7
            0x04,       // value type = LITERAL(4)
            0x01,
            0x00  // inline literal = TRUE(1)
        );
        $service = JsonBinaryDecoderService::makeJsonBinaryDecoder($binary);
        self::assertSame('[true]', $service->parseToString());
    }

    public function testShouldHaveCorrectConstants(): void
    {
        self::assertSame(0, JsonBinaryDecoderService::SMALL_OBJECT);
        self::assertSame(1, JsonBinaryDecoderService::LARGE_OBJECT);
        self::assertSame(2, JsonBinaryDecoderService::SMALL_ARRAY);
        self::assertSame(3, JsonBinaryDecoderService::LARGE_ARRAY);
        self::assertSame(4, JsonBinaryDecoderService::LITERAL);
        self::assertSame(12, JsonBinaryDecoderService::STRING);
        self::assertSame(0, JsonBinaryDecoderService::LITERAL_NULL);
        self::assertSame(1, JsonBinaryDecoderService::LITERAL_TRUE);
        self::assertSame(2, JsonBinaryDecoderService::LITERAL_FALSE);
    }
}
