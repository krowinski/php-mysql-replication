<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

class XidDTO extends EventDTO
{
    private $type = ConstEventsNames::XID;
    private $xid;

    public function __construct(
        EventInfo $eventInfo,
        string $xid
    ) {
        parent::__construct($eventInfo);

        $this->xid = $xid;
    }

    public function getXid(): string
    {
        return $this->xid;
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Transaction ID: ' . $this->xid . PHP_EOL;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}