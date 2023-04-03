<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

class QueryDTO extends EventDTO
{
    private $executionTime;
    private $query;
    private $database;
    private $type = ConstEventsNames::QUERY;

    public function __construct(
        EventInfo $eventInfo,
        string $database,
        int $executionTime,
        string $query
    ) {
        parent::__construct($eventInfo);

        $this->executionTime = $executionTime;
        $this->query = $query;
        $this->database = $database;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Database: ' . $this->database . PHP_EOL .
            'Execution time: ' . $this->executionTime . PHP_EOL .
            'Query: ' . $this->query . PHP_EOL;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
