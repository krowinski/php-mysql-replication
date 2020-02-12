<?php
declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

class JsonBinaryDecoderValue
{
    private $isResolved;
    private $value;
    private $type;
    private $offset;

    public function __construct(
        bool $isResolved,
        $value,
        int $type,
        int $offset = null
    ) {
        $this->isResolved = $isResolved;
        $this->value = $value;
        $this->type = $type;
        $this->offset = $offset;
    }

    public function getOffset(): int
    {
        return $this->offset;
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