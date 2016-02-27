<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Event\EventInfo;

/**
 * Class RowsDTO
 * @package MySQLReplication\DTO
 */
class RowsDTO extends EventDTO implements \JsonSerializable
{
    /**
     * @var array
     */
    private $values;
    /**
     * @var string
     */
    private $database;
    /**
     * @var string
     */
    private $table;
    /**
     * @var int
     */
    private $affected;
    /**
     * @var int
     */
    private $changedRows;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param $database
     * @param $table
     * @param $affected
     * @param $changedRows
     * @param array $values
     */
    public function __construct(
        EventInfo $eventInfo,
        $database,
        $table,
        $affected,
        $changedRows,
        array $values
    ) {
        parent::__construct($eventInfo);

        $this->database = $database;
        $this->table = $table;
        $this->affected = $affected;
        $this->changedRows = $changedRows;
        $this->values = $values;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getAffected()
    {
        return $this->affected;
    }

    /**
     * @return int
     */
    public function getChangedRows()
    {
        return $this->changedRows;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return '';
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
        'Table: ' . $this->table . PHP_EOL .
        'Affected columns: ' . $this->affected . PHP_EOL .
        'Changed rows: ' . $this->changedRows . PHP_EOL .
        'Values: ' . print_r($this->values, true) . PHP_EOL;
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
}