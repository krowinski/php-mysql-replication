<?php

namespace MySQLReplication\Event;

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
     * EventInfo constructor.
     * @param int $timestamp
     * @param string $type
     * @param int $id
     * @param int $size
     * @param int $pos
     * @param string $flag
     * @param bool $checkSum
     */
    public function __construct(
        $timestamp,
        $type,
        $id,
        $size,
        $pos,
        $flag,
        $checkSum
    ) {
        $this->timestamp = $timestamp;
        $this->type = $type;
        $this->id = $id;
        $this->size = $size;
        $this->pos = $pos;
        $this->flag = $flag;
        $this->checkSum = $checkSum;
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        return (new \DateTime())->setTimestamp($this->timestamp)->format('c');
    }

    /**
     * @return string
     */
    public function getSizeNoHeader()
    {
        return (true === $this->checkSum ? $this->size - 23 : $this->size - 19);
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
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}