<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

/**
 * Class WriteRowsDTO
 * @package MySQLReplication\DTO
 */
class WriteRowsDTO extends RowsDTO
{
    /**
     * @return string
     */
    public function getType()
    {
        return ConstEventsNames::WRITE;
    }
}