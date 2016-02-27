<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMap;

/**
 * Class TableMapDTO
 * @package MySQLReplication\DTO
 */
class TableMapDTO extends EventDTO implements \JsonSerializable
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
    public function __toString()
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return TableMap
     */
    public function getTableMap()
    {
        return $this->tableMap;
    }
}