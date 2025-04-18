<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Event\DTO\RowsQueryDTO;

/**
 * The Rows_query event is within the binary log when the MySQL Option `binlog_rows_query_log_events`
 * is enabled.
 *
 * @see https://dev.mysql.com/doc/dev/mysql-server/latest/classRows__query__log__event.html
 */
class RowsQueryEvent extends EventCommon
{
    public function makeRowsQueryDTO(): RowsQueryDTO
    {
        $this->binaryDataReader->advance(1);
        return new RowsQueryDTO(
            $this->eventInfo,
            $this->binaryDataReader->read($this->eventInfo->getSizeNoHeader() - 1),
        );
    }
}
