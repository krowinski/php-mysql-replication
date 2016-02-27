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
     * @var string
     */
    protected $type = ConstEventsNames::UPDATE;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}