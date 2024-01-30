<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogServerInfo;

abstract class EventCommon
{
    public function __construct(
        protected EventInfo $eventInfo,
        protected BinaryDataReader $binaryDataReader,
        protected BinLogServerInfo $binLogServerInfo
    ) {
    }
}
