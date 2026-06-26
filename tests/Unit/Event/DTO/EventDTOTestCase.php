<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\DTO;

use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\ColumnDTOCollection;
use MySQLReplication\Event\RowEvent\TableMap;
use PHPUnit\Framework\TestCase;

abstract class EventDTOTestCase extends TestCase
{
    protected function makeEventInfo(int $timestamp = 1620000000): EventInfo
    {
        $binLogCurrent = new BinLogCurrent();
        $binLogCurrent->setBinFileName('binlog.001');
        $binLogCurrent->setBinLogPosition('0');

        return new EventInfo(
            $timestamp,
            ConstEventType::QUERY_EVENT->value,
            1,
            100,
            '0',
            0,
            false,
            $binLogCurrent
        );
    }

    protected function makeTableMap(): TableMap
    {
        return new TableMap('test_db', 'test_table', '1', 2, new ColumnDTOCollection());
    }
}
