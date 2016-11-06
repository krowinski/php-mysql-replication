<?php


namespace Unit\BinaryDataReader;


use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderBuilder;
use Unit\BaseTest;

/**
 * Class BinaryDataReaderBuilderTest
 * @package Unit\BinaryDataReader
 * @covers MySQLReplication\BinaryDataReader\BinaryDataReaderBuilder
 */
class BinaryDataReaderBuilderTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldBuild()
    {
        $expected = 'foo';

        $builder = new BinaryDataReaderBuilder();
        $builder->withBinaryData($expected);
        $class = $builder->build();

        self::assertAttributeEquals($expected, 'binaryData', $builder);
        self::assertInstanceOf(BinaryDataReader::class, $class);
    }
}