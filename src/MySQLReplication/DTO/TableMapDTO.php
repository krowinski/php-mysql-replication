<?php

namespace MySQLReplication\DTO;

/**
 * Class TableMapDTO
 * @package MySQLReplication\DTO
 */
class TableMapDTO extends EventDTO implements \JsonSerializable
{
    /**
     * @var int
     */
    private $tableId;
    /**
     * @var string
     */
    private $database;
    /**
     * @var string
     */
    private $table;
    /**
     * @var
     */
    private $columns;

    /**
     * TableMapDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $tableId
     * @param $database
     * @param $table
     * @param $columns
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $tableId,
        $database,
        $table,
        $columns
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->tableId = $tableId;
        $this->database = $database;
        $this->table = $table;
        $this->columns = $columns;
    }

    /**
     * @return int
     */
    public function getTableId()
    {
        return $this->tableId;
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
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
        '=== ' . get_class($this) . ' === ' . PHP_EOL .
        'Date: ' . $this->date . PHP_EOL .
        'Log position: ' . $this->binLogPos . PHP_EOL .
        'Event size: ' . $this->eventSize . PHP_EOL .
        'Read bytes: ' . $this->readBytes . PHP_EOL .
        'Table: ' . $this->table . PHP_EOL .
        'Database: ' . $this->database . PHP_EOL .
        'Table Id: ' . $this->tableId . PHP_EOL .
        'Columns: ' . print_r($this->columns, true) . PHP_EOL;
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