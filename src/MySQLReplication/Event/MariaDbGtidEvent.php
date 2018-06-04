<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\MariaDbGtidLogDTO;

/**
 * Class MariaGtidEvent
 * @package MySQLReplication\Event
 */
class MariaDbGtidEvent extends EventCommon
{
    /**
     * @return MariaDbGtidLogDTO
     */
    public function makeMariaDbGTIDLogDTO(): MariaDbGtidLogDTO
    {
        $sequenceNumber = $this->binaryDataReader->readUInt64();
        $domainId = $this->binaryDataReader->readUInt32();
        $flag = $this->binaryDataReader->readUInt8();

        $this->eventInfo->getBinLogCurrent()->setMariaDbGtid($sequenceNumber);

        return new MariaDbGtidLogDTO(
            $this->eventInfo,
            $flag,
            $domainId,
            $sequenceNumber
        );
    }
}