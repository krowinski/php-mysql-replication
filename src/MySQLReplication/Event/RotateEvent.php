<?php

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\RotateDTO;

/**
 * Class RotateEvent
 * @package MySQLReplication\Event
 */
class RotateEvent  extends EventCommon
{
    /**
     * @return RotateDTO
     */
    public function makeRotateEventDTO()
    {
        $pos = $this->binaryDataReader->readUInt64();
        $binFileName = $this->binaryDataReader->read($this->eventInfo->getSizeNoHeader());

        return new RotateDTO(
            $this->eventInfo,
            $pos,
            $binFileName
        );
    }
}
