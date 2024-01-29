<?php

declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

use MySQLReplication\Exception\MySQLReplicationException;

class JsonBinaryDecoderException extends MySQLReplicationException
{
    public const UNKNOWN_JSON_TYPE_MESSAGE = 'Unknown JSON type: ';
    public const UNKNOWN_JSON_TYPE_CODE = 300;
}
