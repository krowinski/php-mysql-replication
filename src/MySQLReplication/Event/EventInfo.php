<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinLog\BinLogCurrent;

/**
 * Class EventInfo
 * @package MySQLReplication\BinLog
 */
class EventInfo implements \JsonSerializable
{
    /**
     * @var int
     */
    private $timestamp;
    /**
     * @var string
     */
    private $type;
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $size;
    /**
     * @var int
     */
    private $pos;
    /**
     * @var int
     */
    private $flag;
    /**
     * @var bool
     */
    private $checkSum;
    /**
     * @var int
     */
    private $sizeNoHeader;
    /**
     * @var string
     */
    private $dateTime;
    /**
     * @var BinLogCurrent
     */
    private $binLogCurrent;

    /**
     * EventInfo constructor.
     * @param int $timestamp
     * @param int $type
     * @param int $id
     * @param int $size
     * @param int $pos
     * @param int $flag
     * @param bool $checkSum
     * @param BinLogCurrent $binLogCurrent
     */
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

    /**
     * @return BinLogCurrent
     */
    public function getBinLogCurrent(): BinLogCurrent
    {
        return $this->binLogCurrent;
    }

    /**
     * @return string
     */
    public function getDateTime(): string
    {
        if (empty($this->dateTime)) {
            $this->dateTime = date('c', $this->timestamp);
        }

        return $this->dateTime;
    }

    /**
     * @return int
     */
    public function getSizeNoHeader(): int
    {
        if (empty($this->sizeNoHeader)) {
            $this->sizeNoHeader = (true === $this->checkSum ? $this->size - 23 : $this->size - 19);
        }

        return $this->sizeNoHeader;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getPos(): int
    {
        return $this->pos;
    }

    /**
     * @return int
     */
    public function getFlag(): int
    {
        return $this->flag;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}