<?php

namespace MySQLReplication\BinaryDataReader;

/**
 * Class BinaryDataReaderService
 * @package MySQLReplication\BinaryDataReader
 */
class BinaryDataReaderService
{
    /**
     * @param $binaryData
     * @return BinaryDataReader
     */
    public function makePackageFromBinaryData($binaryData)
    {
        $packageBuilder = new BinaryDataReaderBuilder();
        $packageBuilder->withBinaryData($binaryData);

        return $packageBuilder->build();
    }
}