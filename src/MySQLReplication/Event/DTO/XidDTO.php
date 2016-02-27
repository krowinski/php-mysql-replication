<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class Xid
 * @package MySQLReplication\DTO
 */
class XidDTO extends EventDTO implements \JsonSerializable
{
    /**
     * @var string
     */
    private $type = ConstEventsNames::XID;
    /**
     * @var
     */
    private $xid;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param $xid
     */
    public function __construct(
        EventInfo $eventInfo,
        $xid
    ) {
        parent::__construct($eventInfo);

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
        '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
        'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
        'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
        'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
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