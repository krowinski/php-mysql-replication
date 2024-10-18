<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

class DeleteRowsDTO extends RowsDTO
{
    protected $type = ConstEventsNames::DELETE;

    public function getType(): string
    {
        return $this->type;
    }
}