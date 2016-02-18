<?php

namespace MySQLReplication\DTO;

/**
 * Class GTIDLogEventDTO
 * @package MySQLReplication\DTO
 */
class GTIDLogDTO extends EventDTO
{
    /**
     * @var bool
     */
    private $commit;
    /**
     * @var string
     */
    private $gtid;

    /**
     * GTIDLogEventDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $commit
     * @param $gtid
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $commit,
        $gtid
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->commit = $commit;
        $this->gtid = $gtid;
    }

    /**
     * @return boolean
     */
    public function isCommit()
    {
        return $this->commit;
    }

    /**
     * @return string
     */
    public function getGtid()
    {
        return $this->gtid;
    }
}