<?php
declare(strict_types=1);

namespace MySQLReplication\Definitions;

class ConstEventsNames
{
    public const TABLE_MAP = 'tableMap';
    public const GTID = 'gtid';
    public const XID = 'xid';
    public const QUERY = 'query';
    public const ROTATE = 'rotate';
    public const DELETE = 'delete';
    public const UPDATE = 'update';
    public const WRITE = 'write';
    public const MARIADB_GTID = 'mariadb gtid';
    public const FORMAT_DESCRIPTION = 'format description';
    public const HEARTBEAT = 'heartbeat';
}