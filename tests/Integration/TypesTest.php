<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

class TypesTest extends BaseCase
{
    public function testShouldBeDecimal(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(2,1))';
        $insert_query = 'INSERT INTO test VALUES(4.2)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(4.2, $event->values[0]['test']);
    }

    public function testShouldBeDecimalLongValues(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(20,10))';
        $insert_query = 'INSERT INTO test VALUES(9000000123.123456)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertSame($expect = '9000000123.1234560000', $value = $event->values[0]['test']);
        self::assertSame(strlen($expect), strlen($value));
    }

    public function testShouldBeDecimalLongValues2(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(20,10))';
        $insert_query = 'INSERT INTO test VALUES(9000000123.0000012345)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('9000000123.0000012345', $event->values[0]['test']);
    }

    public function testShouldBeDecimalNegativeValues(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(20,10), test2 DECIMAL(11,4), test3 DECIMAL(40,30))';
        $insert_query = 'INSERT INTO test VALUES(-42000.123456, -51.1234, -51.123456789098765432123456789)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-42000.1234560000', $event->values[0]['test']);
        self::assertEquals('-51.1234', $event->values[0]['test2']);
        self::assertEquals('-51.123456789098765432123456789000', $event->values[0]['test3']);
    }

    public function testShouldBeDecimalTwoValues(): void
    {
        $create_query = 'CREATE TABLE test ( test DECIMAL(2,1), test2 DECIMAL(20,10) )';
        $insert_query = 'INSERT INTO test VALUES(4.2, 42000.123456)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('4.2', $event->values[0]['test']);
        self::assertEquals('42000.1234560000', $event->values[0]['test2']);
    }

    public function testShouldBeDecimalZeroScale1(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
        $insert_query = 'INSERT INTO test VALUES(10)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('10', $event->values[0]['test']);
    }

    public function testShouldBeDecimalZeroScale2(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
        $insert_query = 'INSERT INTO test VALUES(12345678912345678912345)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('12345678912345678912345', $event->values[0]['test']);
    }

    public function testShouldBeDecimalZeroScale3(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
        $insert_query = 'INSERT INTO test VALUES(100000.0)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('100000', $event->values[0]['test']);
    }

    public function testShouldBeDecimalZeroScale4(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
        $insert_query = 'INSERT INTO test VALUES(-100000.0)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-100000', $event->values[0]['test']);
    }

    public function testShouldBeDecimalZeroScale5(): void
    {
        $create_query = 'CREATE TABLE test (test DECIMAL(23,0))';
        $insert_query = 'INSERT INTO test VALUES(-1234567891234567891234)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('-1234567891234567891234', $event->values[0]['test']);
    }

    public function testShouldBeTinyInt(): void
    {
        $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test TINYINT)';
        $insert_query = 'INSERT INTO test VALUES(255, -128)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(255, $event->values[0]['id']);
        self::assertEquals(-128, $event->values[0]['test']);
    }

    public function testShouldBeMapsToBooleanTrue(): void
    {
        $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
        $insert_query = 'INSERT INTO test VALUES(1, TRUE)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->values[0]['id']);
        self::assertEquals(1, $event->values[0]['test']);
    }

    public function testShouldBeMapsToBooleanFalse(): void
    {
        $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
        $insert_query = 'INSERT INTO test VALUES(1, FALSE)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->values[0]['id']);
        self::assertEquals(0, $event->values[0]['test']);
    }

    public function testShouldBeMapsToNone(): void
    {
        $create_query = 'CREATE TABLE test (id TINYINT UNSIGNED NOT NULL, test BOOLEAN)';
        $insert_query = 'INSERT INTO test VALUES(1, NULL)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1, $event->values[0]['id']);
        self::assertEquals(null, $event->values[0]['test']);
    }

    public function testShouldBeMapsToShort(): void
    {
        $create_query = 'CREATE TABLE test (id SMALLINT UNSIGNED NOT NULL, test SMALLINT)';
        $insert_query = 'INSERT INTO test VALUES(65535, -32768)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(65535, $event->values[0]['id']);
        self::assertEquals(-32768, $event->values[0]['test']);
    }

    public function testShouldBeLong(): void
    {
        $create_query = 'CREATE TABLE test (id INT UNSIGNED NOT NULL, test INT)';
        $insert_query = 'INSERT INTO test VALUES(4294967295, -2147483648)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(4294967295, $event->values[0]['id']);
        self::assertEquals(-2147483648, $event->values[0]['test']);
    }

    public function testShouldBeFloat(): void
    {
        $create_query = 'CREATE TABLE test (id FLOAT NOT NULL, test FLOAT)';
        $insert_query = 'INSERT INTO test VALUES(42.42, -84.84)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(42.42, $event->values[0]['id']);
        self::assertEquals(-84.84, $event->values[0]['test']);
    }

    public function testShouldBeDouble(): void
    {
        $create_query = 'CREATE TABLE test (id DOUBLE NOT NULL, test DOUBLE)';
        $insert_query = 'INSERT INTO test VALUES(42.42, -84.84)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(42.42, $event->values[0]['id']);
        self::assertEquals(-84.84, $event->values[0]['test']);
    }

    public function testShouldBeTimestamp(): void
    {
        $create_query = 'CREATE TABLE test (test TIMESTAMP);';
        $insert_query = 'INSERT INTO test VALUES("1984-12-03 12:33:07")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->values[0]['test']);
    }

    public function testShouldBeTimestampMySQL56(): void
    {
        /*
         * https://mariadb.com/kb/en/library/microseconds-in-mariadb/
         * MySQL 5.6 introduced microseconds using a slightly different implementation to MariaDB 5.3.
         * Since MariaDB 10.1, MariaDB has defaulted to the MySQL format ...
         */
        if ($this->mySQLReplicationFactory?->getServerInfo()->isMariaDb() && $this->checkForVersion(10.1)) {
            self::markTestIncomplete('Only for mariadb 10.1 or higher');
        } elseif ($this->checkForVersion(5.6)) {
            self::markTestIncomplete('Only for mysql 5.6 or higher');
        }

        $create_query = 'CREATE TABLE test (test0 TIMESTAMP(0),
            test1 TIMESTAMP(1),
            test2 TIMESTAMP(2),
            test3 TIMESTAMP(3),
            test4 TIMESTAMP(4),
            test5 TIMESTAMP(5),
            test6 TIMESTAMP(6));';
        $insert_query = 'INSERT INTO test VALUES(
            "1984-12-03 12:33:07",
            "1984-12-03 12:33:07.1",
            "1984-12-03 12:33:07.12",
            "1984-12-03 12:33:07.123",
            "1984-12-03 12:33:07.1234",
            "1984-12-03 12:33:07.12345",
            "1984-12-03 12:33:07.123456")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->values[0]['test0']);
        self::assertEquals('1984-12-03 12:33:07.100000', $event->values[0]['test1']);
        self::assertEquals('1984-12-03 12:33:07.120000', $event->values[0]['test2']);
        self::assertEquals('1984-12-03 12:33:07.123000', $event->values[0]['test3']);
        self::assertEquals('1984-12-03 12:33:07.123400', $event->values[0]['test4']);
        self::assertEquals('1984-12-03 12:33:07.123450', $event->values[0]['test5']);
        self::assertEquals('1984-12-03 12:33:07.123456', $event->values[0]['test6']);
    }

    public function testShouldBeLongLong(): void
    {
        $create_query = 'CREATE TABLE test (id BIGINT UNSIGNED NOT NULL, test BIGINT)';
        $insert_query = 'INSERT INTO test VALUES(18446744073709551615, -9223372036854775808)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('18446744073709551615', $event->values[0]['id']);
        self::assertEquals('-9223372036854775808', $event->values[0]['test']);
    }

    public function testShouldBeInt24(): void
    {
        $create_query = 'CREATE TABLE test (id MEDIUMINT UNSIGNED NOT NULL, test MEDIUMINT, test2 MEDIUMINT, test3 MEDIUMINT, test4 MEDIUMINT, test5 MEDIUMINT)';
        $insert_query = 'INSERT INTO test VALUES(16777215, 8388607, -8388608, 8, -8, 0)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(16777215, $event->values[0]['id']);
        self::assertEquals(8388607, $event->values[0]['test']);
        self::assertEquals(-8388608, $event->values[0]['test2']);
        self::assertEquals(8, $event->values[0]['test3']);
        self::assertEquals(-8, $event->values[0]['test4']);
        self::assertEquals(0, $event->values[0]['test5']);
    }

    public function testShouldBeDate(): void
    {
        $create_query = 'CREATE TABLE test (test DATE);';
        $insert_query = 'INSERT INTO test VALUES("1984-12-03")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03', $event->values[0]['test']);
    }

    public function testShouldBeZeroDate(): void
    {
        $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
        $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "0000-01-21")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
        self::assertNull($event->values[0]['test2']);
    }

    public function testShouldBeZeroMonth(): void
    {
        $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
        $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "2015-00-21")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
        self::assertNull($event->values[0]['test2']);
    }

    public function testShouldBeZeroDay(): void
    {
        $create_query = 'CREATE TABLE test (id INTEGER, test DATE, test2 DATE);';
        $insert_query = 'INSERT INTO test (id, test2) VALUES(1, "2015-05-00")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
        self::assertNull($event->values[0]['test2']);
    }

    public function testShouldBeTime(): void
    {
        $create_query = 'CREATE TABLE test (test TIME);';
        $insert_query = 'INSERT INTO test VALUES("12:33:18")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('12:33:18', $event->values[0]['test']);
    }

    public function testShouldBeZeroTime(): void
    {
        $create_query = 'CREATE TABLE test (id INTEGER, test TIME NOT NULL DEFAULT 0);';
        $insert_query = 'INSERT INTO test (id) VALUES(1)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('00:00:00', $event->values[0]['test']);
    }

    public function testShouldBeDateTime(): void
    {
        $create_query = 'CREATE TABLE test (test DATETIME);';
        $insert_query = 'INSERT INTO test VALUES("1984-12-03 12:33:07")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('1984-12-03 12:33:07', $event->values[0]['test']);
    }

    public function testShouldBeZeroDateTime(): void
    {
        $create_query = 'CREATE TABLE test (id INTEGER, test DATETIME NOT NULL DEFAULT 0);';
        $insert_query = 'INSERT INTO test (id) VALUES(1)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
    }

    public function testShouldBeBrokenDateTime(): void
    {
        $create_query = 'CREATE TABLE test (test DATETIME NOT NULL);';
        $insert_query = 'INSERT INTO test VALUES("2013-00-00 00:00:00")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
    }

    public function testShouldReturnNullOnZeroDateDateTime(): void
    {
        $create_query = 'CREATE TABLE test (test DATETIME NOT NULL);';
        $insert_query = 'INSERT INTO test VALUES("0000-00-00 00:00:00")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
    }

    public function testShouldBeYear(): void
    {
        $create_query = 'CREATE TABLE test (test YEAR(4), test2 YEAR, test3 YEAR)';
        $insert_query = 'INSERT INTO test VALUES(1984, 1984, 0000)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(1984, $event->values[0]['test']);
        self::assertEquals(1984, $event->values[0]['test2']);
        self::assertNull($event->values[0]['test3']);
    }

    public function testShouldBeVarChar(): void
    {
        $create_query = 'CREATE TABLE test (test VARCHAR(242)) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
    }

    public function testShouldBe1024CharsLongVarChar(): void
    {
        $expected = str_repeat('-', 1024);

        $create_query = 'CREATE TABLE test (test VARCHAR(1024)) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("' . $expected . '")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals($expected, $event->values[0]['test']);
    }

    public function testShouldBeBit(): void
    {
        $create_query = 'CREATE TABLE test (
            test BIT(6),
            test2 BIT(16),
            test3 BIT(12),
            test4 BIT(9),
            test5 BIT(64)
         );';
        $insert_query = "INSERT INTO test VALUES(
            b'100010',
            b'1000101010111000',
            b'100010101101',
            b'101100111',
            b'1101011010110100100111100011010100010100101110111011101011011010'
        )";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('100010', $event->values[0]['test']);
        self::assertEquals('1000101010111000', $event->values[0]['test2']);
        self::assertEquals('100010101101', $event->values[0]['test3']);
        self::assertEquals('101100111', $event->values[0]['test4']);
        self::assertEquals(
            '1101011010110100100111100011010100010100101110111011101011011010',
            $event->values[0]['test5']
        );
    }

    public function testShouldBeEnum(): void
    {
        $create_query = 'CREATE TABLE test
            (
                test ENUM("a", "ba", "c"),
                test2 ENUM("a", "ba", "c"),
                test3 ENUM("foo", "bar")
            )
            CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("ba", "a", "not_exists")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('ba', $event->values[0]['test']);
        self::assertEquals('a', $event->values[0]['test2']);
        self::assertEquals('', $event->values[0]['test3']);
    }

    public function testShouldBeSet(): void
    {
        $create_query = 'CREATE TABLE test (test SET("a", "ba", "c"), test2 SET("a", "ba", "c")) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("ba,a,c", "a,c")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(['a', 'ba', 'c'], $event->values[0]['test']);
        self::assertEquals(['a', 'c'], $event->values[0]['test2']);
    }

    public function testShouldBeTinyBlob(): void
    {
        $create_query = 'CREATE TABLE test (test TINYBLOB, test2 TINYTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
        self::assertEquals('World', $event->values[0]['test2']);
    }

    public function testShouldBeMediumBlob(): void
    {
        $create_query = 'CREATE TABLE test (test MEDIUMBLOB, test2 MEDIUMTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
        self::assertEquals('World', $event->values[0]['test2']);
    }

    public function testShouldBeNullOnBooleanType(): void
    {
        $create_query = 'CREATE TABLE test (test BOOLEAN);';
        $insert_query = 'INSERT INTO test VALUES(NULL)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
    }

    public function testShouldBeLongBlob(): void
    {
        $create_query = 'CREATE TABLE test (test LONGBLOB, test2 LONGTEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
        self::assertEquals('World', $event->values[0]['test2']);
    }

    /**
     * https://dev.mysql.com/doc/internals/en/mysql-packet.html
     * https://dev.mysql.com/doc/internals/en/sending-more-than-16mbyte.html
     */
    public function shouldBeLongerTextThan16Mb(): void
    {
        $long_text_data = '';
        for ($i = 0; $i < 40000000; ++$i) {
            $long_text_data .= 'a';
        }
        $create_query = 'CREATE TABLE test (data LONGTEXT);';
        $insert_query = 'INSERT INTO test (data) VALUES ("' . $long_text_data . '")';
        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(strlen($long_text_data), strlen($event->values[0]['data']));

        $long_text_data = null;
    }

    public function testShouldBeBlob(): void
    {
        $create_query = 'CREATE TABLE test (test BLOB, test2 TEXT) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello", "World")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
        self::assertEquals('World', $event->values[0]['test2']);
    }

    public function testShouldBeString(): void
    {
        $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("Hello")';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals('Hello', $event->values[0]['test']);
    }

    public function testShouldBeGeometry(): void
    {
        $prefix = 'ST_';
        if ($this->checkForVersion(5.6)) {
            $prefix = '';
        }

        $create_query = 'CREATE TABLE test (test GEOMETRY);';
        $insert_query = 'INSERT INTO test VALUES(' . $prefix . 'GeomFromText("POINT(1 1)"))';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals(
            '000000000101000000000000000000f03f000000000000f03f',
            bin2hex($event->values[0]['test'])
        );
    }

    public function testShouldBeNull(): void
    {
        $create_query = 'CREATE TABLE test (
            test TINYINT NULL DEFAULT NULL,
            test2 TINYINT NULL DEFAULT NULL,
            test3 TINYINT NULL DEFAULT NULL,
            test4 TINYINT NULL DEFAULT NULL,
            test5 TINYINT NULL DEFAULT NULL,
            test6 TINYINT NULL DEFAULT NULL,
            test7 TINYINT NULL DEFAULT NULL,
            test8 TINYINT NULL DEFAULT NULL,
            test9 TINYINT NULL DEFAULT NULL,
            test10 TINYINT NULL DEFAULT NULL,
            test11 TINYINT NULL DEFAULT NULL,
            test12 TINYINT NULL DEFAULT NULL,
            test13 TINYINT NULL DEFAULT NULL,
            test14 TINYINT NULL DEFAULT NULL,
            test15 TINYINT NULL DEFAULT NULL,
            test16 TINYINT NULL DEFAULT NULL,
            test17 TINYINT NULL DEFAULT NULL,
            test18 TINYINT NULL DEFAULT NULL,
            test19 TINYINT NULL DEFAULT NULL,
            test20 TINYINT NULL DEFAULT NULL
            )';
        $insert_query = 'INSERT INTO test (test, test2, test3, test7, test20) VALUES(NULL, -128, NULL, 42, 84)';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertNull($event->values[0]['test']);
        self::assertEquals(-128, $event->values[0]['test2']);
        self::assertNull($event->values[0]['test3']);
        self::assertEquals(42, $event->values[0]['test7']);
        self::assertEquals(84, $event->values[0]['test20']);
    }

    public function testShouldBeEncodedLatin1(): void
    {
        $this->connection->executeStatement('SET CHARSET latin1');

        $string = "\00e9";

        $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET latin1 COLLATE latin1_bin;';
        $insert_query = 'INSERT INTO test VALUES("' . $string . '");';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals($string, $event->values[0]['test']);
    }

    public function testShouldBeEncodedUTF8(): void
    {
        $this->connection->executeStatement('SET CHARSET utf8');

        $string = "\20ac";

        $create_query = 'CREATE TABLE test (test CHAR(12)) CHARACTER SET utf8 COLLATE utf8_bin;';
        $insert_query = 'INSERT INTO test VALUES("' . $string . '");';

        $event = $this->createAndInsertValue($create_query, $insert_query);

        self::assertEquals($string, $event->values[0]['test']);
    }

    public function testShouldBeJson(): void
    {
        if ($this->checkForVersion(5.7) || $this->mySQLReplicationFactory?->getServerInfo()->isMariaDb()) {
            self::markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $create_query = 'CREATE TABLE t1 (i INT, j JSON)';
        $insert_query = "INSERT INTO t1 VALUES 
            (0, NULL) , 
            (1, '{\"a\": 2}'),
            (2, '[1,2]'),
            (3, '{\"a\":\"b\", \"c\":\"d\",\"ab\":\"abc\", \"bc\": [\"x\", \"y\"]}'),
            (4, '[\"here\", [\"I\", \"am\"], \"!!!\"]'),
            (5, '\"scalar string\"'),
            (6, 'true'),
            (7, 'false'),
            (8, 'null'),
            (9, '-1'),
            (10, CAST(CAST(1 AS UNSIGNED) AS JSON)),
            (11, '32767'),
            (12, '32768'),
            (13, '-32768'),
            (14, '-32769'),
            (15, '2147483647'),
            (16, '2147483648'),
            (17, '-2147483648'),
            (18, '-2147483649'),
            (19, '18446744073709551615'),
            (20, '18446744073709551616'),
            (21, '3.14'),
            (22, '{}'),
            (23, '[]'),
            -- (24, CAST(CAST('2015-01-15 23:24:25' AS DATETIME) AS JSON)),
            -- (25, CAST(CAST('23:24:25' AS TIME) AS JSON)),
            -- (125, CAST(CAST('23:24:25.12' AS TIME(3)) AS JSON)),
            -- (225, CAST(CAST('23:24:25.0237' AS TIME(3)) AS JSON)),
            -- (26, CAST(CAST('2015-01-15' AS DATE) AS JSON)),
            -- (27, CAST(TIMESTAMP'2015-01-15 23:24:25' AS JSON)),
            -- (127, CAST(TIMESTAMP'2015-01-15 23:24:25.12' AS JSON)),
            -- (227, CAST(TIMESTAMP'2015-01-15 23:24:25.0237' AS JSON)),
            -- (327, CAST(UNIX_TIMESTAMP('2015-01-15 23:24:25') AS JSON)),
            -- (28, CAST(ST_GeomFromText('POINT(1 1)') AS JSON)),
            (29, CAST('[]' AS CHAR CHARACTER SET 'ascii')),
            -- (30, CAST(x'cafe' AS JSON)),
            -- (31, CAST(x'cafebabe' AS JSON)),
            -- (100, CONCAT('{\"', REPEAT('a', 64 * 1024 - 1), '\":123}')),
            (101, '{\"bool\": true}'),
            (102, '{\"bool\": false}'),
            (103, '{\"null\": null}'),
            (104, '[\"\\\\\"test\"]')
        ";

        $event = $this->createAndInsertValue($create_query, $insert_query);

        $results = $event->values;

        self::assertEquals(null, $results[0]['j']);
        self::assertEquals('{"a":2}', $results[1]['j']);
        self::assertEquals('[1,2]', $results[2]['j']);
        self::assertEquals('{"a":"b","c":"d","ab":"abc","bc":["x","y"]}', $results[3]['j']);
        self::assertEquals('["here",["I","am"],"!!!"]', $results[4]['j']);
        self::assertEquals('"scalar string"', $results[5]['j']);
        self::assertEquals('true', $results[6]['j']);
        self::assertEquals('false', $results[7]['j']);
        self::assertEquals('"null"', $results[8]['j']);
        self::assertEquals('"-1"', $results[9]['j']);
        self::assertEquals('"1"', $results[10]['j']);
        self::assertEquals('"32767"', $results[11]['j']);
        self::assertEquals('"32768"', $results[12]['j']);
        self::assertEquals('"-32768"', $results[13]['j']);
        self::assertEquals('"-32769"', $results[14]['j']);
        self::assertEquals('"2147483647"', $results[15]['j']);
        self::assertEquals('"2147483648"', $results[16]['j']);
        self::assertEquals('"-2147483648"', $results[17]['j']);
        self::assertEquals('"-2147483649"', $results[18]['j']);
        self::assertEquals('"18446744073709551615"', $results[19]['j']);
        self::assertEquals('"1.844674407371E+19"', $results[20]['j']);
        self::assertEquals('"3.14"', $results[21]['j']);
        self::assertEquals('{}', $results[22]['j']);
        self::assertEquals('[]', $results[23]['j']);
        self::assertEquals('[]', $results[24]['j']);
        self::assertEquals('{"bool":true}', $results[25]['j']);
        self::assertEquals('{"bool":false}', $results[26]['j']);
        self::assertEquals('{"null":null}', $results[27]['j']);
        self::assertEquals('["\"test"]', $results[28]['j']);
    }
}
