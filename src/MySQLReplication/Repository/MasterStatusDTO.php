<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

class MasterStatusDTO
{
    private $position;
    private $file;

    public function __construct(
        int $position,
        string $file
    ) {
        $this->position = $position;
        $this->file = $file;
    }

    public static function makeFromArray(array $data): self
    {
        return new self(
            (int)$data['Position'],
            (string)$data['File']
        );
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getFile(): string
    {
        return $this->file;
    }
}