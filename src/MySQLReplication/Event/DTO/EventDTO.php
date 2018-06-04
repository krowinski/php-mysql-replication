<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Event\EventInfo;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventDTO
 * @package MySQLReplication\DTO
 *
 * @see https://dev.mysql.com/doc/internals/en/event-meanings.html
 */
abstract class EventDTO extends Event implements \JsonSerializable
{
    /**
     * @var EventInfo
     */
    protected $eventInfo;

    /**
     * EventDTO constructor.
     * @param EventInfo $eventInfo
     */
    public function __construct(
        EventInfo $eventInfo
    ) {
        $this->eventInfo = $eventInfo;
    }

    /**
     * @return EventInfo
     */
    public function getEventInfo(): EventInfo
    {
        return $this->eventInfo;
    }

    /**
     * @return string
     */
    abstract public function getType(): string;

    /**
     * @return string
     */
    abstract public function __toString(): string;
}