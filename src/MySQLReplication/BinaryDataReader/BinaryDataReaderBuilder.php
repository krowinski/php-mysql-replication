<?php

namespace MySQLReplication\BinaryDataReader;

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