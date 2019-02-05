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
        $this->jsonString .= '"' . self::escapeJsonString($val) . '"';
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

    /**
     * Some characters needs to be escaped
     * @see http://www.json.org/
     * @see https://stackoverflow.com/questions/1048487/phps-json-encode-does-not-escape-all-json-control-characters
     * @param string $value
     * @return string
     */
    private static function escapeJsonString($value)
    {
        return str_replace(
            ["\\", '/', '"', "\n", "\r", "\t", "\x08", "\x0c"],
            ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"],
            $value
        );
    }
}