<?php
declare(strict_types=1);

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
     * @param int $type
     */
    public function __construct(
        bool $isResolved,
        $value,
        int $type
    )
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
     * @return bool
     */
    public function isIsResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}