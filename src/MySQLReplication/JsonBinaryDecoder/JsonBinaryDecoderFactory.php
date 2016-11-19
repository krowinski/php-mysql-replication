<?php


namespace MySQLReplication\JsonBinaryDecoder;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class JsonBinaryDecoderFactory
 * @package MySQLReplication\JsonBinaryDecoder
 */
class JsonBinaryDecoderFactory
{
    /**
     * @param string $jsonBinaryData
     * @return JsonBinaryDecoderService
     */
    public function makeJsonBinaryDecoder($jsonBinaryData)
    {
        return (new JsonBinaryDecoderService(
            new BinaryDataReader($jsonBinaryData),
            new JsonBinaryDecoderFormatter())
        );
    }
}
