<?php


namespace MySQLReplication\JsonBinaryDecoder;

use MySQLReplication\Exception\MySQLReplicationException;

/**
 * Class JsonBinaryDecoderException
 * @package MySQLReplication\JsonBinaryDecoder
 */
class JsonBinaryDecoderException extends MySQLReplicationException
{
    const UNKNOWN_JSON_TYPE_MESSAGE = 'Unknown JSON type: ';
    const UNKNOWN_JSON_TYPE_CODE = 1;
}