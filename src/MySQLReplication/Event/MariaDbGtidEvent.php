<?php

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
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
     */
    public function makeMariaDbGTIDLogDTO()
    {
        $sequenceNumber = $this->binaryDataReader->readUInt64();
        $domainId = $this->binaryDataReader->readUInt32();
        $flag = $this->binaryDataReader->readUInt8();

        return new MariaDbGtidLogDTO(
            $this->eventInfo,
            $flag,
            $domainId,
            $sequenceNumber
        );
    }
}