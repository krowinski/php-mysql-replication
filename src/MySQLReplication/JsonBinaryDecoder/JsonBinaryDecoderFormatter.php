<?php
declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

/**
 * Class JsonBinaryDecoderFormatter
 * @package MySQLReplication\JsonBinaryDecoder
 */
class JsonBinaryDecoderFormatter
{
    /**
     * @var string
     */
    public $jsonString = '';

    /**
     * @param bool $bool
     */
    public function formatValueBool(bool $bool): void
    {
        $this->jsonString .= var_export($bool, true);
    }

    /**
     * @param int $val
     */
    public function formatValueNumeric(int $val): void
    {
        $this->jsonString .= $val;
    }

    /**
     * @param mixed $val
     */
    public function formatValue($val): void
    {
        $this->jsonString .= '"' . $val . '"';
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

    /**
     * @param string $name
     */
    public function formatName(string $name): void
    {
        $this->jsonString .= '"' . $name . '":';
    }

    public function formatValueNull(): void
    {
        $this->jsonString .= 'null';
    }

    /**
     * @return string
     */
    public function getJsonString(): string
    {
        return $this->jsonString;
    }
}