<?php
declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

class JsonBinaryDecoderValue
{
    private $isResolved;
    private $value;
    private $type;

    public function __construct(
        bool $isResolved,
        $value,
        int $type
    ) {
        $this->isResolved = $isResolved;
        $this->value = $value;
        $this->type = $type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isIsResolved(): bool
    {
        return $this->isResolved;
    }

    public function getType(): int
    {
        return $this->type;
    }
}