<?php

declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use JsonSerializable;
use MySQLReplication\Event\EventInfo;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @see https://dev.mysql.com/doc/internals/en/event-meanings.html
 */
abstract class EventDTO extends GenericEvent implements JsonSerializable
{
    public function __construct(
        protected EventInfo $eventInfo
    ) {
        parent::__construct();
    }

    abstract public function __toString(): string;

    public function getEventInfo(): EventInfo
    {
        return $this->eventInfo;
    }

    abstract public function getType(): string;
}
