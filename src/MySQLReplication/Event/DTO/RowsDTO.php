<?php

declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

abstract class RowsDTO extends EventDTO
{
    public function __construct(
        EventInfo $eventInfo,
        public readonly TableMap $tableMap,
        public readonly int $changedRows,
        public readonly array $values
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
            'Table: ' . $this->tableMap->table . PHP_EOL .
            'Affected columns: ' . $this->tableMap->columnsAmount . PHP_EOL .
            'Changed rows: ' . $this->changedRows . PHP_EOL .
            'Values: ' . print_r($this->values, true) . PHP_EOL;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
