<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class GTIDLogDTO
 * @package MySQLReplication\Event\DTO
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
     * @var string
     */
    private $type = ConstEventsNames::GTID;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param bool $commit
     * @param string $gtid
     */
    public function __construct(
        EventInfo $eventInfo,
        $commit,
        $gtid
    ) {
        parent::__construct($eventInfo);

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
        '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
        'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
        'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
        'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
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