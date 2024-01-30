<?php

declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

class UpdateRowsDTO extends RowsDTO
{
    protected ConstEventsNames $type = ConstEventsNames::UPDATE;

    public function getType(): string
    {
        return $this->type->value;
    }
}
