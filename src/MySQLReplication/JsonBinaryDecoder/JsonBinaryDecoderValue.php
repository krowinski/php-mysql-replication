<?php

declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

class JsonBinaryDecoderValue
{
    public function __construct(
        public bool $isResolved,
        public mixed $value,
        public int $type,
        public ?int $offset = null
    ) {
    }
}
