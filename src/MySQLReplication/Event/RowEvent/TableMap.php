<?php
declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use JsonSerializable;

class TableMap implements JsonSerializable
{
    private $database;
    private $table;
    private $tableId;
    private $columnsAmount;
    private $columnDTOCollection;

    public function __construct(
        string $database,
        string $table,
        string $tableId,
        int $columnsAmount,
        ColumnDTOCollection $columnDTOCollection
    ) {
        $this->database = $database;
        $this->table = $table;
        $this->tableId = $tableId;
        $this->columnsAmount = $columnsAmount;
        $this->columnDTOCollection = $columnDTOCollection;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getTableId(): string
    {
        return $this->tableId;
    }

    public function getColumnsAmount(): int
    {
        return $this->columnsAmount;
    }

    /**
     * @return ColumnDTOCollection|ColumnDTO[]
     */
    public function getColumnDTOCollection(): ColumnDTOCollection
    {
        return $this->columnDTOCollection;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
