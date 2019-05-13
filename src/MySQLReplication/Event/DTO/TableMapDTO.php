<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

/**
 * Class TableMapDTO
 * @package MySQLReplication\DTO
 */
class TableMapDTO extends EventDTO
{
    /**
     * @var string
     */
    private $type = ConstEventsNames::TABLE_MAP;
    /**
     * @var TableMap
     */
    private $tableMap;

    /**
     * TableMapDTO constructor.
     * @param EventInfo $eventInfo
     * @param TableMap $tableMap
     */
    public function __construct(
        EventInfo $eventInfo,
        TableMap $tableMap
    ) {
        parent::__construct($eventInfo);

        $this->tableMap = $tableMap;
    }

    /**
     * @return string
     */
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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return TableMap
     */
    public function getTableMap(): TableMap
    {
        return $this->tableMap;
    }
}