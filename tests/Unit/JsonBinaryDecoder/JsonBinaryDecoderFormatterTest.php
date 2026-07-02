<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\JsonBinaryDecoder;

use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFormatter;
use PHPUnit\Framework\TestCase;

class JsonBinaryDecoderFormatterTest extends TestCase
{
    private JsonBinaryDecoderFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsonBinaryDecoderFormatter();
    }

    public function testShouldStartWithEmptyString(): void
    {
        self::assertSame('', $this->formatter->getJsonString());
    }

    public function testShouldFormatBeginObject(): void
    {
        $this->formatter->formatBeginObject();
        self::assertSame('{', $this->formatter->getJsonString());
    }

    public function testShouldFormatEndObject(): void
    {
        $this->formatter->formatEndObject();
        self::assertSame('}', $this->formatter->getJsonString());
    }

    public function testShouldFormatBeginArray(): void
    {
        $this->formatter->formatBeginArray();
        self::assertSame('[', $this->formatter->getJsonString());
    }

    public function testShouldFormatEndArray(): void
    {
        $this->formatter->formatEndArray();
        self::assertSame(']', $this->formatter->getJsonString());
    }

    public function testShouldFormatNextEntry(): void
    {
        $this->formatter->formatNextEntry();
        self::assertSame(',', $this->formatter->getJsonString());
    }

    public function testShouldFormatName(): void
    {
        $this->formatter->formatName('mykey');
        self::assertSame('"mykey":', $this->formatter->getJsonString());
    }

    public function testShouldFormatValue(): void
    {
        $this->formatter->formatValue('hello');
        self::assertSame('"hello"', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueWithEscaping(): void
    {
        $this->formatter->formatValue("line1\nline2");
        self::assertSame('"line1\\nline2"', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueWithQuoteEscaping(): void
    {
        $this->formatter->formatValue('say "hello"');
        self::assertSame('"say \\"hello\\""', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueBoolTrue(): void
    {
        $this->formatter->formatValueBool(true);
        self::assertSame('true', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueBoolFalse(): void
    {
        $this->formatter->formatValueBool(false);
        self::assertSame('false', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueNumeric(): void
    {
        $this->formatter->formatValueNumeric(42);
        self::assertSame('42', $this->formatter->getJsonString());
    }

    public function testShouldFormatValueNull(): void
    {
        $this->formatter->formatValueNull();
        self::assertSame('null', $this->formatter->getJsonString());
    }

    public function testShouldBuildCompleteJsonObject(): void
    {
        $this->formatter->formatBeginObject();
        $this->formatter->formatName('key');
        $this->formatter->formatValue('value');
        $this->formatter->formatEndObject();

        self::assertSame('{"key":"value"}', $this->formatter->getJsonString());
    }

    public function testShouldBuildCompleteJsonArray(): void
    {
        $this->formatter->formatBeginArray();
        $this->formatter->formatValueNumeric(1);
        $this->formatter->formatNextEntry();
        $this->formatter->formatValueNumeric(2);
        $this->formatter->formatEndArray();

        self::assertSame('[1,2]', $this->formatter->getJsonString());
    }

    public function testShouldEscapeBackspace(): void
    {
        $this->formatter->formatValue("\x08");
        self::assertSame('"\\b"', $this->formatter->getJsonString());
    }

    public function testShouldEscapeFormFeed(): void
    {
        $this->formatter->formatValue("\x0c");
        self::assertSame('"\\f"', $this->formatter->getJsonString());
    }
}
