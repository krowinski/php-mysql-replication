<?php

declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

class TableMapDTO extends EventDTO
{
    private ConstEventsNames $type = ConstEventsNames::TABLE_MAP;

    public function __construct(
        EventInfo $eventInfo,
        public readonly TableMap $tableMap
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
            'Database: ' . $this->tableMap->database . PHP_EOL .
            'Table Id: ' . $this->tableMap->tableId . PHP_EOL .
            'Columns amount: ' . $this->tableMap->columnsAmount . PHP_EOL;
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
