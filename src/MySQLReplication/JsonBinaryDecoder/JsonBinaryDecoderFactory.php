<?php
declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class JsonBinaryDecoderFactory
 * @package MySQLReplication\JsonBinaryDecoder
 */
class JsonBinaryDecoderFactory
{
    /**
     * @param string $data
     * @return JsonBinaryDecoderService
     */
    public static function makeJsonBinaryDecoder(string $data): JsonBinaryDecoderService
    {
        return new JsonBinaryDecoderService(
            new BinaryDataReader($data),
            new JsonBinaryDecoderFormatter()
        );
    }
}