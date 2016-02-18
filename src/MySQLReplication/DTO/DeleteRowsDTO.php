<?php

namespace MySQLReplication\DTO;


class DeleteRowsDTO extends EventDTO
{
    /**
     * @var []
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
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $database
     * @param $table
     * @param $affected
     * @param $changedRows
     * @param $values
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $database,
        $table,
        $affected,
        $changedRows,
        array $values
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->database = $database;
        $this->table = $table;
        $this->affected = $affected;
        $this->changedRows = $changedRows;
        $this->values = $values;
    }

    /**
     * @return mixed
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
}