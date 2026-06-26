<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\JsonBinaryDecoder;

use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderService;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderValue;
use PHPUnit\Framework\TestCase;

class JsonBinaryDecoderValueTest extends TestCase
{
    public function testShouldConstructWithResolvedValue(): void
    {
        $value = new JsonBinaryDecoderValue(true, 'hello', JsonBinaryDecoderService::STRING);

        self::assertTrue($value->isResolved);
        self::assertSame('hello', $value->value);
        self::assertSame(JsonBinaryDecoderService::STRING, $value->type);
        self::assertNull($value->offset);
    }

    public function testShouldConstructWithUnresolvedValue(): void
    {
        $value = new JsonBinaryDecoderValue(false, null, JsonBinaryDecoderService::STRING, 42);

        self::assertFalse($value->isResolved);
        self::assertNull($value->value);
        self::assertSame(JsonBinaryDecoderService::STRING, $value->type);
        self::assertSame(42, $value->offset);
    }

    public function testShouldConstructWithNullLiteralValue(): void
    {
        $value = new JsonBinaryDecoderValue(true, null, JsonBinaryDecoderService::LITERAL);

        self::assertTrue($value->isResolved);
        self::assertNull($value->value);
    }

    public function testShouldConstructWithBoolValue(): void
    {
        $trueValue = new JsonBinaryDecoderValue(true, true, JsonBinaryDecoderService::LITERAL);
        $falseValue = new JsonBinaryDecoderValue(true, false, JsonBinaryDecoderService::LITERAL);

        self::assertTrue($trueValue->value);
        self::assertFalse($falseValue->value);
    }

    public function testShouldConstructWithNumericValue(): void
    {
        $value = new JsonBinaryDecoderValue(true, 12345, JsonBinaryDecoderService::INT32);

        self::assertSame(12345, $value->value);
        self::assertSame(JsonBinaryDecoderService::INT32, $value->type);
    }
}
