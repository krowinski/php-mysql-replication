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
    private $data = '';

    /**
     * @param string $data
     */
    public function withBinaryData($data)
    {
        $this->data = $data;
    }

    /**
     * @return BinaryDataReader
     */
    public function build()
    {
        return new BinaryDataReader($this->data);
    }
}