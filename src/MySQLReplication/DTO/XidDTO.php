<?php

namespace MySQLReplication\DTO;

/**
 * Class Xid
 * @package MySQLReplication\DTO
 */
class XidDTO extends EventDTO
{
    /**
     * @var
     */
    private $xid;

    /**
     * GTIDLogEventDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $xid
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $xid
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->xid = $xid;
    }

    /**
     * @return mixed
     */
    public function getXid()
    {
        return $this->xid;
    }

}