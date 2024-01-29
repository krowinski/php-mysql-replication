<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\DTO\GTIDLogDTO;

class GtidEvent extends EventCommon
{
    public function makeGTIDLogDTO(): GTIDLogDTO
    {
        $commit_flag = $this->binaryDataReader->readUInt8() === 1;
        $sid = BinaryDataReader::unpack('H*', $this->binaryDataReader->read(16))[1];
        $gno = $this->binaryDataReader->readUInt64();

        $gtid = vsprintf(
            '%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s',
            str_split($sid)
        ) . ':' . $gno;

        $this->eventInfo->binLogCurrent
            ->setGtid($gtid);

        return new GTIDLogDTO($this->eventInfo, $commit_flag, $gtid);
    }
}
