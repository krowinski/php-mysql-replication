<?php

namespace MySQLReplication\BinaryDataReader;

/**
 * Class BinaryDataReaderBuilder
 * @package MySQLReplication\BinaryDataReader
 */
class BinaryDataReaderBuilder
{
    private $binaryData = '';

    /**
     * @param string $binaryData
     */
    public function withBinaryData($binaryData)
    {
        $this->binaryData = $binaryData;
    }

    public function build()
    {
        return new BinaryDataReader(
            $this->binaryData
        );
    }
}