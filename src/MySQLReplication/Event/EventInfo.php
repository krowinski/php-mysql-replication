<?php

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
     * @var string
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
     * @param string $type
     * @param int $id
     * @param int $size
     * @param int $pos
     * @param string $flag
     * @param bool $checkSum
     * @param BinLogCurrent $binLogCurrent
     */
    public function __construct(
        $timestamp,
        $type,
        $id,
        $size,
        $pos,
        $flag,
        $checkSum,
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
    public function getBinLogCurrent()
    {
        return $this->binLogCurrent;
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        if (empty($this->dateTime)) {
            $this->dateTime = date('c', $this->timestamp);
        }

        return $this->dateTime;
    }

    /**
     * @return string
     */
    public function getSizeNoHeader()
    {
        if (empty($this->sizeNoHeader)) {
            $this->sizeNoHeader = (true === $this->checkSum ? $this->size - 23 : $this->size - 19);
        }

        return $this->sizeNoHeader;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @return string
     */
    public function getFlag()
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