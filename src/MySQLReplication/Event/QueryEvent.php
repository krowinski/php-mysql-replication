<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\QueryDTO;

/**
 * @see https://dev.mysql.com/doc/internals/en/query-event.html
 */
class QueryEvent extends EventCommon
{
    public function makeQueryDTO(): QueryDTO
    {
        $this->binaryDataReader->advance(4);
        $executionTime = $this->binaryDataReader->readUInt32();
        $schemaLength = $this->binaryDataReader->readUInt8();
        $this->binaryDataReader->advance(2);
        $statusVarsLength = $this->binaryDataReader->readUInt16();
        $this->binaryDataReader->advance($statusVarsLength);
        $schema = $this->binaryDataReader->read($schemaLength);
        $this->binaryDataReader->advance(1);
        $query = $this->binaryDataReader->read($this->eventInfo->getSizeNoHeader() - 13 - $statusVarsLength - $schemaLength - 1);

        return new QueryDTO(
            $this->eventInfo,
            $schema,
            $executionTime,
            $query
        );
    }
}