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
     * @var string
     */
    protected $type = ConstEventsNames::DELETE;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}