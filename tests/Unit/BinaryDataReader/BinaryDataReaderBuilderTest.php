<?php


namespace MySQLReplication\Tests\Unit\BinaryDataReader;

use MySQLReplication\BinaryDataReader\BinaryDataReaderBuilder;
use MySQLReplication\Tests\Unit\BaseTest;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class BinaryDataReaderBuilderTest
 * @package MySQLReplication\Tests\Unit\BinaryDataReader
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

        self::assertAttributeEquals($expected, 'data', $builder);
        self::assertInstanceOf(BinaryDataReader::class, $class);
    }
}