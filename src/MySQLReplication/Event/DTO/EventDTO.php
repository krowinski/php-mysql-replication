<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use JsonSerializable;
use MySQLReplication\Event\EventInfo;
use Symfony\Component\EventDispatcher\Event;

/**
 * @see https://dev.mysql.com/doc/internals/en/event-meanings.html
 */
abstract class EventDTO extends Event implements JsonSerializable
{
    protected $eventInfo;

    public function __construct(
        EventInfo $eventInfo
    ) {
        $this->eventInfo = $eventInfo;
    }

    public function getEventInfo(): EventInfo
    {
        return $this->eventInfo;
    }

    abstract public function getType(): string;

    abstract public function __toString(): string;
}