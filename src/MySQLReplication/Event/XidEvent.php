<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\XidDTO;

class XidEvent extends EventCommon
{
    public function makeXidDTO(): XidDTO
    {
        return new XidDTO(
            $this->eventInfo,
            $this->binaryDataReader->readUInt64()
        );
    }
}