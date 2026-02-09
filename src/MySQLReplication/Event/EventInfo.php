<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use JsonSerializable;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Definitions\ConstEventType;

class EventInfo implements JsonSerializable
{
    private ?int $sizeNoHeader;
    private ?string $dateTime;
    private ?string $typeName;

    public function __construct(
        public readonly int $timestamp,
        public readonly int $type,
        public readonly int $serverId,
        public readonly int $size,
        public readonly string $pos,
        public readonly int $flag,
        public readonly bool $checkSum,
        public readonly BinLogCurrent $binLogCurrent
    ) {
        if ($pos > 0) {
            $this->binLogCurrent->setBinLogPosition($pos);
        }
        $this->sizeNoHeader = $this->dateTime = null;
        $this->typeName = ConstEventType::tryFrom($this->type)?->name;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function getDateTime(): ?string
    {
        if ($this->timestamp === 0) {
            return null;
        }

        if (empty($this->dateTime)) {
            $this->dateTime = date('c', $this->timestamp);
        }

        return $this->dateTime;
    }

    public function getSizeNoHeader(): int
    {
        if (empty($this->sizeNoHeader)) {
            $this->sizeNoHeader = ($this->checkSum === true ? $this->size - 23 : $this->size - 19);
        }

        return $this->sizeNoHeader;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
