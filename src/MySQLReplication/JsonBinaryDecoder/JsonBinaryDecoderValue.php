<?php


namespace MySQLReplication\JsonBinaryDecoder;

/**
 * Class JsonBinaryDecoderValue
 * @package MySQLReplication\JsonBinaryDecoder
 */
class JsonBinaryDecoderValue
{
    /**
     * @var bool
     */
    private $isResolved;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @var string
     */
    private $type;

    /**
     * JsonBinaryDecoderValue constructor.
     * @param bool $isResolved
     * @param mixed $value
     * @param string $type
     */
    public function __construct($isResolved, $value, $type)
    {
        $this->isResolved = $isResolved;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return boolean
     */
    public function isIsResolved()
    {
        return $this->isResolved;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}