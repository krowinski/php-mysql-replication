<?php

namespace MySQLReplication\Definitions;

/**
 * Class ConstEventsNames
 * @package MySQLReplication\Definitions
 */
class ConstEventsNames
{
    const TABLE_MAP = 'tableMap';
    const GTID = 'gtid';
    const XID = 'xid';
    const QUERY = 'query';
    const ROTATE = 'rotate';
    const DELETE = 'delete';
    const UPDATE = 'update';
    const WRITE = 'write';
    const MARIADB_GTID = 'mariadb gtid';
}