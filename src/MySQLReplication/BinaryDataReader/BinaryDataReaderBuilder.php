<?php

namespace MySQLReplication\BinaryDataReader;

/**
 * Class BinaryDataReaderBuilder
 * @package MySQLReplication\BinaryDataReader
 */
class BinaryDataReaderBuilder
{
    /**
     * @var string
     */
    private $binaryData = '';

    /**
     * @param string $binaryData
     */
    public function withBinaryData($binaryData)
    {
        $this->binaryData = $binaryData;
    }

    /**
     * @return BinaryDataReader
     */
    public function build()
    {
        return new BinaryDataReader(
            $this->binaryData
        );
    }
}