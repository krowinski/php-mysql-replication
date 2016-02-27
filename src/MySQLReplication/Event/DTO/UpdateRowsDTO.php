<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

/**
 * Class UpdateRowsDTO
 * @package MySQLReplication\DTO
 */
class UpdateRowsDTO extends RowsDTO
{
    /**
     * @return string
     */
    public function getType()
    {
        return ConstEventsNames::UPDATE;
    }
}