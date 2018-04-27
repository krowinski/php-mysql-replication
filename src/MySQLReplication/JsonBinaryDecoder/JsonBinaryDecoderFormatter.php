<?php


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
    public function formatValueBool($bool)
    {
        $this->jsonString .= var_export($bool, true);
    }

    /**
     * @param int $val
     */
    public function formatValueNumeric($val)
    {
        $this->jsonString .= $val;
    }

    /**
     * @param string $val
     */
    public function formatValue($val)
    {
        $this->jsonString .= '"' . $val . '"';
    }

    public function formatEndObject()
    {
        $this->jsonString .= '}';
    }

    public function formatBeginArray()
    {
        $this->jsonString .= '[';
    }

    public function formatEndArray()
    {
        $this->jsonString .= ']';
    }

    public function formatBeginObject()
    {
        $this->jsonString .= '{';
    }

    public function formatNextEntry()
    {
        $this->jsonString .= ',';
    }

    /**
     * @param string $name
     */
    public function formatName($name)
    {
        $this->jsonString .= '"' . $name . '":';
    }

    public function formatValueNull()
    {
        $this->jsonString .= 'null';
    }

    /**
     * @return string
     */
    public function getJsonString()
    {
        return $this->jsonString;
    }
}