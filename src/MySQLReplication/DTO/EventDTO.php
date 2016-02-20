<?php

namespace MySQLReplication\DTO;

/**
 * Class EventDTO
 * @package MySQLReplication\DTO
 */
class EventDTO implements \JsonSerializable
{
    /**
     * @var int
     */
    protected $date;
    /**
     * @var int
     */
    protected $binLogPos;
    /**
     * @var int
     */
    protected $eventSize;
    /**
     * @var int
     */
    protected $readBytes;

    /**
     * EventDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes
    ) {
        $this->date = $date;
        $this->binLogPos = $binLogPos;
        $this->eventSize = $eventSize;
        $this->readBytes = $readBytes;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getBinLogPos()
    {
        return $this->binLogPos;
    }

    /**
     * @return int
     */
    public function getEventSize()
    {
        return $this->eventSize;
    }

    /**
     * @return int
     */
    public function getReadBytes()
    {
        return $this->readBytes;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
        '=== ' . get_class($this) . ' === ' . PHP_EOL .
        'Date: ' . $this->date . PHP_EOL .
        'Log position: ' . $this->binLogPos . PHP_EOL .
        'Event size: ' . $this->eventSize . PHP_EOL .
        'Read bytes: ' . $this->readBytes . PHP_EOL;
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