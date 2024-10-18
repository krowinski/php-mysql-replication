<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

abstract class EventCommon
{
    protected $eventInfo;
    protected $binaryDataReader;

    public function __construct(
        EventInfo $eventInfo,
        BinaryDataReader $binaryDataReader
    ) {
        $this->eventInfo = $eventInfo;
        $this->binaryDataReader = $binaryDataReader;
    }
}