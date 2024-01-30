<?php

declare(strict_types=1);

namespace MySQLReplication\Repository;

readonly class FieldDTO
{
    public function __construct(
        public string $columnName,
        public ?string $collationName,
        public ?string $characterSetName,
        public string $columnComment,
        public string $columnType,
        public string $columnKey
    ) {
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
                'COLUMN_KEY' => '',
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
}
