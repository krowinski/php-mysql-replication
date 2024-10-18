<?php
declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

class JsonBinaryDecoderFormatter
{
    public $jsonString = '';

    public function formatValueBool(bool $bool): void
    {
        $this->jsonString .= var_export($bool, true);
    }

    public function formatValueNumeric(int $val): void
    {
        $this->jsonString .= $val;
    }

    public function formatValue($val): void
    {
        $this->jsonString .= '"' . self::escapeJsonString($val) . '"';
    }

    /**
     * Some characters needs to be escaped
     * @see http://www.json.org/
     * @see https://stackoverflow.com/questions/1048487/phps-json-encode-does-not-escape-all-json-control-characters
     */
    private static function escapeJsonString($value): string
    {
        return str_replace(
            ["\\", '/', '"', "\n", "\r", "\t", "\x08", "\x0c"],
            ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"],
            (string)$value
        );
    }

    public function formatEndObject(): void
    {
        $this->jsonString .= '}';
    }

    public function formatBeginArray(): void
    {
        $this->jsonString .= '[';
    }

    public function formatEndArray(): void
    {
        $this->jsonString .= ']';
    }

    public function formatBeginObject(): void
    {
        $this->jsonString .= '{';
    }

    public function formatNextEntry(): void
    {
        $this->jsonString .= ',';
    }

    public function formatName(string $name): void
    {
        $this->jsonString .= '"' . $name . '":';
    }

    public function formatValueNull(): void
    {
        $this->jsonString .= 'null';
    }

    public function getJsonString(): string
    {
        return $this->jsonString;
    }
}