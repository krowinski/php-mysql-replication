<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Event\DTO\RotateDTO;

/**
 * @see https://dev.mysql.com/doc/internals/en/rotate-event.html
 */
class RotateEvent extends EventCommon
{
    private $binLogServerInfo;

    public function __construct(
        BinLogServerInfo $binLogServerInfo,
        EventInfo $eventInfo,
        BinaryDataReader $binaryDataReader
    ) {
        parent::__construct($eventInfo, $binaryDataReader);
        $this->binLogServerInfo = $binLogServerInfo;
    }

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
        if ($this->binLogServerInfo->isMariaDb() && $this->binLogServerInfo->getRevision() <= 10) {
            return 0;
        }

        return 8;
    }
}