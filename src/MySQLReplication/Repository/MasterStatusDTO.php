<?php

declare(strict_types=1);

namespace MySQLReplication\Repository;

readonly class MasterStatusDTO
{
    public function __construct(
        public string $position,
        public string $file
    ) {
    }

    public static function makeFromArray(array $data): self
    {
        return new self((string)$data['Position'], (string)$data['File']);
    }
}
