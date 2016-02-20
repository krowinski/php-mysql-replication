<?php

namespace MySQLReplication\DTO;

/**
 * Class Xid
 * @package MySQLReplication\DTO
 */
class XidDTO extends EventDTO implements \JsonSerializable
{
    /**
     * @var
     */
    private $xid;

    /**
     * GTIDLogEventDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $xid
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $xid
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->xid = $xid;
    }

    /**
     * @return mixed
     */
    public function getXid()
    {
        return $this->xid;
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
        'Read bytes: ' . $this->readBytes . PHP_EOL .
        'Transaction ID: ' . $this->xid . PHP_EOL;
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