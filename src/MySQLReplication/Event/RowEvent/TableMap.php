<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use JsonSerializable;

class TableMap implements JsonSerializable
{
    public function __construct(
        public string $database,
        public string $table,
        public string $tableId,
        public int $columnsAmount,
        public ColumnDTOCollection $columnDTOCollection
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
