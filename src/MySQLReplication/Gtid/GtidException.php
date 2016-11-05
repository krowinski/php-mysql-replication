<?php

namespace MySQLReplication\Gtid;

/**
 * Class GtidException
 * @package MySQLReplication\Gtid
 */
class GtidException extends \Exception
{
    const INCORRECT_GTID_MESSAGE = 'Incorrect gtid';
    const INCORRECT_GTID_CODE = 1;
}