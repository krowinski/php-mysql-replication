<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;

/**
 * Class WriteRowsDTO
 * @package MySQLReplication\DTO
 */
class WriteRowsDTO extends RowsDTO
{
    /**
     * @var string
     */
    protected $type = ConstEventsNames::WRITE;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}