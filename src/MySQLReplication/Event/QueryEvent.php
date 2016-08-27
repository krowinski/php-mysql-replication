<?php

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\QueryDTO;

/**
 * Class QueryEvent
 * @package MySQLReplication\Event
 */
class QueryEvent extends EventCommon
{
    /**
     * @return QueryDTO
     */
    public function makeQueryDTO()
    {
        $this->binaryDataReader->advance(4);
        $execution_time = $this->binaryDataReader->readUInt32();
        $schema_length = $this->binaryDataReader->readUInt8();
        $this->binaryDataReader->advance(2);
        $status_vars_length = $this->binaryDataReader->readUInt16();
        $this->binaryDataReader->advance($status_vars_length);
        $schema = $this->binaryDataReader->read($schema_length);
        $this->binaryDataReader->advance(1);
        $query = $this->binaryDataReader->read(
            $this->eventInfo->getSize() - 36 - $status_vars_length - $schema_length - 1
        );

        return new QueryDTO(
            $this->eventInfo,
            $schema,
            $execution_time,
            $query
        );
    }
}