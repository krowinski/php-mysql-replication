<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Event\DTO\RotateDTO;

/**
 * Class RotateEvent
 * @package MySQLReplication\Event
 * @see https://dev.mysql.com/doc/internals/en/rotate-event.html
 */
class RotateEvent extends EventCommon
{
    /**
     * @throws BinaryDataReaderException
     * @return RotateDTO
     */
    public function makeRotateEventDTO()
    {
        $binFilePos = $this->binaryDataReader->readUInt64();
        $binFileName = $this->binaryDataReader->read($this->eventInfo->getSizeNoHeader() - $this->getSizeToRemoveByVersion());

        $this->eventInfo->getBinLogCurrent()->setBinLogPosition($binFilePos);
        $this->eventInfo->getBinLogCurrent()->setBinFileName($binFileName);

        return new RotateDTO(
            $this->eventInfo,
            $binFilePos,
            $binFileName
        );
    }

    /**
     * @return int
     */
    private function getSizeToRemoveByVersion()
    {
        if (BinLogServerInfo::isMariaDb()) {
            return 0;
        }

        return 8;
    }
}