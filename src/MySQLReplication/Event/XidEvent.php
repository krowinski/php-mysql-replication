<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\XidDTO;

/**
 * Class XidEvent
 * @package MySQLReplication\Event
 */
class XidEvent extends EventCommon
{
    /**
     * @return XidDTO
     */
    public function makeXidDTO(): XidDTO
    {
        return new XidDTO(
            $this->eventInfo,
            $this->binaryDataReader->readUInt64()
        );
    }
}