<?php

namespace MySQLReplication\BinaryDataReader;

/**
 * Class BinaryDataReaderFactory
 * @package MySQLReplication\BinaryDataReader
 */
class BinaryDataReaderFactory
{
    /**
     * @param string $data
     * @return BinaryDataReader
     */
    public function makePackageFromBinaryData($data)
    {
        $packageBuilder = new BinaryDataReaderBuilder();
        $packageBuilder->withBinaryData($data);

        return $packageBuilder->build();
    }
}