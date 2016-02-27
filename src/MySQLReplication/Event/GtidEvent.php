<?php

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\GTIDLogDTO;

class GtidEvent extends EventCommon
{
    /**
     * @return GTIDLogDTO
     */
    public function makeGTIDLogDTO()
    {
        $commit_flag = $this->binaryDataReader->readUInt8() == 1;
        $sid = unpack('H*', $this->binaryDataReader->read(16))[1];
        $gno = $this->binaryDataReader->readUInt64();

        return new GTIDLogDTO(
            $this->eventInfo,
            $commit_flag,
            vsprintf('%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s', str_split($sid)) . ':' . $gno
        );
    }
}