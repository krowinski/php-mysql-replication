<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException;
use MySQLReplication\Event\DTO\GTIDLogDTO;

/**
 * Class GtidEvent
 * @package MySQLReplication\Event
 */
class GtidEvent extends EventCommon
{
    /**
     * @return GTIDLogDTO
     * @throws BinaryDataReaderException
     */
    public function makeGTIDLogDTO()
    {
        $commit_flag = 1 === $this->binaryDataReader->readUInt8();
        $sid = unpack('H*', $this->binaryDataReader->read(16))[1];
        $gno = $this->binaryDataReader->readUInt64();

        return new GTIDLogDTO(
            $this->eventInfo,
            $commit_flag,
            vsprintf('%s%s%s%s%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s-%s%s%s%s%s%s%s%s%s%s%s%s', str_split($sid)) . ':' . $gno
        );
    }
}