<?php


namespace Unit\BinaryDataReader;


use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use Unit\BaseTest;

/**
 * Class BinaryDataReaderServiceTest
 * @package Unit\BinaryDataReader
 * @covers MySQLReplication\BinaryDataReader\BinaryDataReaderService
 */
class BinaryDataReaderServiceTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakePackageFromBinaryData()
    {
        $service = (new BinaryDataReaderService())->makePackageFromBinaryData('foo');

        self::assertInstanceOf(BinaryDataReader::class, $service);

    }

}