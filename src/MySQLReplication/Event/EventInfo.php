<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use JsonSerializable;
use MySQLReplication\BinLog\BinLogCurrent;

class EventInfo implements JsonSerializable
{
    private $timestamp;
    private $type;
    private $id;
    private $size;
    private $pos;
    private $flag;
    private $checkSum;
    private $sizeNoHeader;
    private $dateTime;
    private $binLogCurrent;

    public function __construct(
        int $timestamp,
        int $type,
        int $id,
        int $size,
        int $pos,
        int $flag,
        bool $checkSum,
        BinLogCurrent $binLogCurrent
    ) {
        $this->timestamp = $timestamp;
        $this->type = $type;
        $this->id = $id;
        $this->size = $size;
        $this->pos = $pos;
        $this->flag = $flag;
        $this->checkSum = $checkSum;
        $this->binLogCurrent = $binLogCurrent;

        if ($pos > 0) {
            $this->binLogCurrent->setBinLogPosition($pos);
        }
    }

    public function getBinLogCurrent(): BinLogCurrent
    {
        return $this->binLogCurrent;
    }

    public function getDateTime(): string
    {
        if (empty($this->dateTime)) {
            $this->dateTime = date('c', $this->timestamp);
        }

        return $this->dateTime;
    }

    public function getSizeNoHeader(): int
    {
        if (empty($this->sizeNoHeader)) {
            $this->sizeNoHeader = (true === $this->checkSum ? $this->size - 23 : $this->size - 19);
        }

        return $this->sizeNoHeader;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }


    public function getSize(): int
    {
        return $this->size;
    }

    public function getPos(): int
    {
        return $this->pos;
    }

    public function getFlag(): int
    {
        return $this->flag;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}