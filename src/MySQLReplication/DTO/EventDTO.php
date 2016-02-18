<?php

namespace MySQLReplication\DTO;

/**
 * Class EventDTO
 * @package MySQLReplication\DTO
 */
class EventDTO
{
    /**
     * @var int
     */
    private $date;
    /**
     * @var int
     */
    private $binLogPos;
    /**
     * @var int
     */
    private $eventSize;
    /**
     * @var int
     */
    private $readBytes;

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
}