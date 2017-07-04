<?php


namespace MySQLReplication\Unit\BinaryDataReader;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderFactory;
use MySQLReplication\Unit\BaseTest;

/**
 * Class BinaryDataReaderServiceTest
 * @package Unit\BinaryDataReader
 * @covers \MySQLReplication\BinaryDataReader\BinaryDataReader
 */
class BinaryDataReaderServiceTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldMakePackageFromBinaryData()
    {
        $service = (new BinaryDataReaderFactory())->makePackageFromBinaryData('foo');
        self::assertInstanceOf(BinaryDataReader::class, $service);
    }
}