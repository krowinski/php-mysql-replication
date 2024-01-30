<?php

declare(strict_types=1);

namespace MySQLReplication\Gtid;

use MySQLReplication\Exception\MySQLReplicationException;

class GtidException extends MySQLReplicationException
{
    public const INCORRECT_GTID_MESSAGE = 'Incorrect gtid';
    public const INCORRECT_GTID_CODE = 200;
}
