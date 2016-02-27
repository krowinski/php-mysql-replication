<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

/**
 * Class DeleteRowsDTO
 * @package MySQLReplication\DTO
 */
class DeleteRowsDTO extends RowsDTO
{
    /**
     * @return string
     */
    public function getType()
    {
        return ConstEventsNames::DELETE;
    }
}