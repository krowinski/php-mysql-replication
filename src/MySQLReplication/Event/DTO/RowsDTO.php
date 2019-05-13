<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

/**
 * Class RowsDTO
 * @package MySQLReplication\DTO
 */
abstract class RowsDTO extends EventDTO
{
    /**
     * @var array
     */
    private $values;
    /**
     * @var int
     */
    private $changedRows;
    /**
     * @var TableMap
     */
    private $tableMap;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param TableMap $tableMap
     * @param int $changedRows
     * @param array $values
     */
    public function __construct(
        EventInfo $eventInfo,
        TableMap $tableMap,
        int $changedRows,
        array $values
    ) {
        parent::__construct($eventInfo);

        $this->changedRows = $changedRows;
        $this->values = $values;
        $this->tableMap = $tableMap;
    }

    /**
     * @return TableMap
     */
    public function getTableMap(): TableMap
    {
        return $this->tableMap;
    }

    /**
     * @return int
     */
    public function getChangedRows(): int
    {
        return $this->changedRows;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
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
            'Affected columns: ' . $this->tableMap->getColumnsAmount() . PHP_EOL .
            'Changed rows: ' . $this->changedRows . PHP_EOL .
            'Values: ' . print_r($this->values, true) . PHP_EOL;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}