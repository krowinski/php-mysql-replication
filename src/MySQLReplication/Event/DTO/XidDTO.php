<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class XidDTO
 * @package MySQLReplication\Event\DTO
 */
class XidDTO extends EventDTO
{
    /**
     * @var string
     */
    private $type = ConstEventsNames::XID;
    /**
     * @var string
     */
    private $xid;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param string $xid
     */
    public function __construct(
        EventInfo $eventInfo,
        $xid
    ) {
        parent::__construct($eventInfo);

        $this->xid = $xid;
    }

    /**
     * @return string
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
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}