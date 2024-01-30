<?php

declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

class QueryDTO extends EventDTO
{
    private ConstEventsNames $type = ConstEventsNames::QUERY;

    public function __construct(
        EventInfo $eventInfo,
        public readonly string $database,
        public readonly int $executionTime,
        public readonly string $query,
        public readonly int $threadId
    ) {
        parent::__construct($eventInfo);
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->pos . PHP_EOL .
            'Event size: ' . $this->eventInfo->size . PHP_EOL .
            'Database: ' . $this->database . PHP_EOL .
            'Execution time: ' . $this->executionTime . PHP_EOL .
            'Query: ' . $this->query . PHP_EOL .
            'Thread id: ' . $this->threadId . PHP_EOL;
    }

    public function getType(): string
    {
        return $this->type->value;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
