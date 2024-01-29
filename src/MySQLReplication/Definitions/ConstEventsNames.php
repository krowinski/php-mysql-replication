<?php

declare(strict_types=1);

namespace MySQLReplication\Definitions;

enum ConstEventsNames: string
{
    case XID = 'xid';
    case DELETE = 'delete';
    case QUERY = 'query';
    case ROTATE = 'rotate';
    case GTID = 'gtid';
    case MARIADB_GTID = 'mariadb gtid';
    case UPDATE = 'update';
    case HEARTBEAT = 'heartbeat';
    case TABLE_MAP = 'tableMap';
    case WRITE = 'write';
    case FORMAT_DESCRIPTION = 'format description';
}
