<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class EventCommon
 * @package MySQLReplication\Event
 */
abstract class EventCommon
{
    /**
     * @var EventInfo
     */
    protected $eventInfo;
    /**
     * @var BinaryDataReader
     */
    protected $binaryDataReader;

    /**
     * QueryEvent constructor.
     * @param EventInfo $eventInfo
     * @param BinaryDataReader $binaryDataReader
     */
    public function __construct(
        EventInfo $eventInfo,
        BinaryDataReader $binaryDataReader
    ) {
        $this->eventInfo = $eventInfo;
        $this->binaryDataReader = $binaryDataReader;
    }
}