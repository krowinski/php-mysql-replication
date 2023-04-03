<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

class HeartbeatDTO extends EventDTO
{
    protected $type = ConstEventsNames::HEARTBEAT;

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
