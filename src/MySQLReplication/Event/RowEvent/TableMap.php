<?php

namespace MySQLReplication\Event\RowEvent;

/**
 * Class TableMap
 * @package MySQLReplication\Event\RowEvent
 */
class TableMap implements \JsonSerializable
{
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
    private $tableId;
    /**
     * @var int
     */
    private $columnsAmount;
    /**
     * @var array
     */
    private $fields;

    /**
     * TableMap constructor.
     * @param string $database
     * @param string $table
     * @param int $tableId
     * @param int $columnsAmount
     * @param array $fields
     */
    public function __construct(
        $database,
        $table,
        $tableId,
        $columnsAmount,
        array $fields
    ) {
        $this->database = $database;
        $this->table = $table;
        $this->tableId = $tableId;
        $this->columnsAmount = $columnsAmount;
        $this->fields = $fields;
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
    public function getTableId()
    {
        return $this->tableId;
    }

    /**
     * @return int
     */
    public function getColumnsAmount()
    {
        return $this->columnsAmount;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}