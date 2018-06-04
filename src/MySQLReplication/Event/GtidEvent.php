<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\GTIDLogDTO;

/**
 * Class GtidEvent
 * @package MySQLReplication\Event
 */
class GtidEvent extends EventCommon
{
    /**
     * @return GTIDLogDTO
     */
    public function makeGTIDLogDTO(): GTIDLogDTO
    {
        $commit_flag = 1 === $this->binaryDataReader->readUInt8();
        $sid = unpack('H*', $this->binaryDataReader->read(16))[1];
        $gno = $this->binaryDataReader->readUInt64();

        $gtid = vsprintf('%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s', str_split($sid)) . ':' . $gno;

        $this->eventInfo->getBinLogCurrent()->setGtid($gtid);

        return new GTIDLogDTO(
            $this->eventInfo,
            $commit_flag,
            $gtid
        );
    }
}