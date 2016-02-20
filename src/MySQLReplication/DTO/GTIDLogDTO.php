<?php

namespace MySQLReplication\DTO;

/**
 * Class GTIDLogEventDTO
 * @package MySQLReplication\DTO
 */
class GTIDLogDTO extends EventDTO implements \JsonSerializable
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

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
        '=== ' . __CLASS__ . ' === ' . PHP_EOL .
        'Date: ' . $this->date . PHP_EOL .
        'Log position: ' . $this->binLogPos . PHP_EOL .
        'Event size: ' . $this->eventSize . PHP_EOL .
        'Read bytes: ' . $this->readBytes . PHP_EOL .
        'Commit: ' . var_export($this->commit, true) . PHP_EOL .
        'GTID NEXT: ' . $this->gtid . PHP_EOL;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}