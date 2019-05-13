<?php
declare(strict_types=1);

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
     * @param string $tableId
     * @param int $columnsAmount
     * @param array $fields
     */
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

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getTableId(): string
    {
        return $this->tableId;
    }

    /**
     * @return int
     */
    public function getColumnsAmount(): int
    {
        return $this->columnsAmount;
    }

    /**
     * @return array
     */
    public function getFields(): array
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