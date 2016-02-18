<?php

namespace MySQLReplication\DTO;


/**
 * Class TableMapDTO
 * @package MySQLReplication\DTO
 */
class TableMapDTO extends EventDTO
{
    /**
     * @var
     */
    private $tableId;
    /**
     * @var
     */
    private $database;
    /**
     * @var
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
     * @return mixed
     */
    public function getTableId()
    {
        return $this->tableId;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }
}