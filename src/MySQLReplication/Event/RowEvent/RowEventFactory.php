<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;

class RowEventFactory
{
    public function __construct(
        private RowEventBuilder $rowEventBuilder
    ) {
    }

    public function makeRowEvent(BinaryDataReader $binaryDataReader, EventInfo $eventInfo): RowEvent
    {
        $this->rowEventBuilder->withBinaryDataReader($binaryDataReader);
        $this->rowEventBuilder->withEventInfo($eventInfo);

        return $this->rowEventBuilder->build();
    }
}
