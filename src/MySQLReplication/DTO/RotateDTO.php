<?php

namespace MySQLReplication\DTO;

class RotateDTO extends EventDTO
{
    /**
     * @var int
     */
    private $position;
    /**
     * @var string
     */
    private $next_binlog;

    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $position,
        $next_binlog
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->position = $position;
        $this->next_binlog = $next_binlog;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getNextBinlog()
    {
        return $this->next_binlog;
    }
}