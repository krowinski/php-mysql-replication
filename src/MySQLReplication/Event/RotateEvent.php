<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\RotateDTO;

/**
 * @see https://dev.mysql.com/doc/internals/en/rotate-event.html
 */
class RotateEvent extends EventCommon
{
    public function makeRotateEventDTO(): RotateDTO
    {
        $binFilePos = $this->binaryDataReader->readUInt64();
        $binFileName = $this->binaryDataReader->read(
            $this->eventInfo->getSizeNoHeader() - $this->getSizeToRemoveByVersion()
        );

        $this->eventInfo->binLogCurrent
            ->setBinLogPosition($binFilePos);
        $this->eventInfo->binLogCurrent
            ->setBinFileName($binFileName);

        return new RotateDTO($this->eventInfo, $binFilePos, $binFileName);
    }

    private function getSizeToRemoveByVersion(): int
    {
        if ($this->binLogServerInfo->versionRevision <= 10 && $this->binLogServerInfo->isMariaDb()) {
            return 0;
        }

        return 8;
    }
}
