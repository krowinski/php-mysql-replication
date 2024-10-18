<?php
declare(strict_types=1);

namespace MySQLReplication\Repository;

class FieldDTO
{
    private $columnName;
    private $collationName;
    private $characterSetName;
    private $columnComment;
    private $columnType;
    private $columnKey;

    public function __construct(
        string $columnName,
        ?string $collationName,
        ?string $characterSetName,
        string $columnComment,
        string $columnType,
        string $columnKey
    ) {
        $this->columnName = $columnName;
        $this->collationName = $collationName;
        $this->characterSetName = $characterSetName;
        $this->columnComment = $columnComment;
        $this->columnType = $columnType;
        $this->columnKey = $columnKey;
    }

    public static function makeDummy(int $index): self
    {
        return self::makeFromArray(
            [
                'COLUMN_NAME' => 'DROPPED_COLUMN_' . $index,
                'COLLATION_NAME' => null,
                'CHARACTER_SET_NAME' => null,
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'BLOB',
                'COLUMN_KEY' => ''
            ]
        );
    }

    public static function makeFromArray(array $field): self
    {
        return new self(
            $field['COLUMN_NAME'],
            $field['COLLATION_NAME'],
            $field['CHARACTER_SET_NAME'],
            $field['COLUMN_COMMENT'],
            $field['COLUMN_TYPE'],
            $field['COLUMN_KEY']
        );
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getCollationName(): ?string
    {
        return $this->collationName;
    }

    public function getCharacterSetName(): ?string
    {
        return $this->characterSetName;
    }

    public function getColumnComment(): string
    {
        return $this->columnComment;
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function getColumnKey(): string
    {
        return $this->columnKey;
    }
}