<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

class TableMapDTO extends EventDTO
{
    private $type = ConstEventsNames::TABLE_MAP;
    private $tableMap;

    public function __construct(
        EventInfo $eventInfo,
        TableMap $tableMap
    ) {
        parent::__construct($eventInfo);

        $this->tableMap = $tableMap;
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Table: ' . $this->tableMap->getTable() . PHP_EOL .
            'Database: ' . $this->tableMap->getDatabase() . PHP_EOL .
            'Table Id: ' . $this->tableMap->getTableId() . PHP_EOL .
            'Columns amount: ' . $this->tableMap->getColumnsAmount() . PHP_EOL;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function getTableMap(): TableMap
    {
        return $this->tableMap;
    }
}
