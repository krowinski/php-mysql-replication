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
    private $fields;

    public function __construct(
        string $database,
        string $table,
        string $tableId,
        int $columnsAmount,
        array $fields
    ) {
        $this->database = $database;
        $this->table = $table;
        $this->tableId = $tableId;
        $this->columnsAmount = $columnsAmount;
        $this->fields = $fields;
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

    public function getFields(): array
    {
        return $this->fields;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}