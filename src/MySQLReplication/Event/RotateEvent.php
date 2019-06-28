<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Event\DTO\RotateDTO;

/**
 * @see https://dev.mysql.com/doc/internals/en/rotate-event.html
 */
class RotateEvent extends EventCommon
{
    public function makeRotateEventDTO(): RotateDTO
    {
        $binFilePos = (int)$this->binaryDataReader->readUInt64();
        $binFileName = $this->binaryDataReader->read(
            $this->eventInfo->getSizeNoHeader() - $this->getSizeToRemoveByVersion()
        );

        $this->eventInfo->getBinLogCurrent()->setBinLogPosition($binFilePos);
        $this->eventInfo->getBinLogCurrent()->setBinFileName($binFileName);

        return new RotateDTO(
            $this->eventInfo,
            $binFilePos,
            $binFileName
        );
    }

    private function getSizeToRemoveByVersion(): int
    {
        if (BinLogServerInfo::isMariaDb()) {
            return 0;
        }

        return 8;
    }
}