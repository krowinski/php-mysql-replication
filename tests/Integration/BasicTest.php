<?php
declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;

class BasicTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldGetDeleteEvent(): void
    {
        $this->createAndInsertValue(
            'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))',
            'INSERT INTO test (data) VALUES(\'Hello World\')'
        );

        $this->connection->exec('DELETE FROM test WHERE id = 1');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var DeleteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(DeleteRowsDTO::class, $event);
        self::assertEquals($event->getValues()[0]['id'], 1);
        self::assertEquals($event->getValues()[0]['data'], 'Hello World');
    }

    /**
     * @test
     */
    public function shouldGetUpdateEvent(): void
    {
        $this->createAndInsertValue(
            'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))',
            'INSERT INTO test (data) VALUES(\'Hello\')'
        );

        $this->connection->exec('UPDATE test SET data = \'World\', id = 2 WHERE id = 1');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var UpdateRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(UpdateRowsDTO::class, $event);
        self::assertEquals($event->getValues()[0]['before']['id'], 1);
        self::assertEquals($event->getValues()[0]['before']['data'], 'Hello');
        self::assertEquals($event->getValues()[0]['after']['id'], 2);
        self::assertEquals($event->getValues()[0]['after']['data'], 'World');
    }

    /**
     * @test
     */
    public function shouldGetWriteEventDropTable(): void
    {
        $this->connection->exec($createExpected = 'CREATE TABLE `test` (id INTEGER(11))');
        $this->connection->exec('INSERT INTO `test` VALUES (1)');
        $this->connection->exec($dropExpected = 'DROP TABLE `test`');

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createExpected, $event->getQuery());

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals('BEGIN', $event->getQuery());

        /** @var TableMapDTO $event */
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var WriteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals([], $event->getValues());
        self::assertEquals(0, $event->getChangedRows());

        self::assertInstanceOf(XidDTO::class, $this->getEvent());

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertContains($dropExpected, $event->getQuery());
    }

    /**
     * @test
     */
    public function shouldGetQueryEventCreateTable(): void
    {
        $this->connection->exec(
            $createExpected = 'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createExpected, $event->getQuery());
    }

    /**
     * @test
     */
    public function shouldDropColumn(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly(
            [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2]
        );

        $this->connect();

        $this->connection->exec('CREATE TABLE test_drop_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->exec('INSERT INTO test_drop_column VALUES (1, \'A value\')');
        $this->connection->exec('ALTER TABLE test_drop_column DROP COLUMN data');
        $this->connection->exec('INSERT INTO test_drop_column VALUES (2)');

        /** @var WriteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals(['id' => 1, 'DROPPED_COLUMN_1' => null], $event->getValues()[0]);

        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals(['id' => 2], $event->getValues()[0]);
    }

    /**
     * @test
     */
    public function shouldFilterEvents(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly([ConstEventType::QUERY_EVENT]);

        $this->connect();

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $this->connection->exec($createTableExpected = 'CREATE TABLE test (id INTEGER(11), data VARCHAR(50))');

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createTableExpected, $event->getQuery());
    }

    /**
     * @test
     */
    public function shouldFilterTables(): void
    {
        $expectedTable = 'test_2';
        $expectedValue = 'foobar';

        $this->disconnect();

        $this->configBuilder
            ->withEventsOnly(
                [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2]
            )->withTablesOnly([$expectedTable]);

        $this->connect();

        $this->connection->exec(
            'CREATE TABLE test_2 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );
        $this->connection->exec(
            'CREATE TABLE test_3 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );
        $this->connection->exec(
            'CREATE TABLE test_4 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        $this->connection->exec('INSERT INTO test_4 (data) VALUES (\'foo\')');
        $this->connection->exec('INSERT INTO test_3 (data) VALUES (\'bar\')');
        $this->connection->exec('INSERT INTO test_2 (data) VALUES (\'' . $expectedValue . '\')');

        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals($expectedTable, $event->getTableMap()->getTable());
        self::assertEquals($expectedValue, $event->getValues()[0]['data']);
    }

    /**
     * @test
     */
    public function shouldTruncateTable(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly(
            [ConstEventType::QUERY_EVENT]
        );

        $this->connect();

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $this->connection->exec('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->exec('INSERT INTO test_truncate_column VALUES (1, \'A value\')');
        $this->connection->exec('TRUNCATE TABLE test_truncate_column');

        $event = $this->getEvent();
        self::assertSame('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))', $event->getQuery());
        $event = $this->getEvent();
        self::assertSame('BEGIN', $event->getQuery());
        $event = $this->getEvent();
        self::assertSame('TRUNCATE TABLE test_truncate_column', $event->getQuery());
    }

    public function testMysql8JsonSetPartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || BinLogServerInfo::isMariaDb()) {
            $this->markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8 - C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->exec('UPDATE t1 SET j = JSON_SET(j, \'$.addr.detail.ab\', \'970785C8\')');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var UpdateRowsDTO $event */
        $event = $this->getEvent();

        self::assertInstanceOf(UpdateRowsDTO::class, $event);
        self::assertEquals(
            $expected,
            $event->getValues()[0]['before']['j']
        );
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8"}},"name":"Alice"}',
            $event->getValues()[0]['after']['j']
        );
    }

    public function testMysql8JsonRemovePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || BinLogServerInfo::isMariaDb()) {
            $this->markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->exec('UPDATE t1 SET j = JSON_REMOVE(j, \'$.addr.detail.ab\')');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var UpdateRowsDTO $event */
        $event = $this->getEvent();

        self::assertInstanceOf(UpdateRowsDTO::class, $event);
        self::assertEquals(
            $expected,
            $event->getValues()[0]['before']['j']
        );
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{}},"name":"Alice"}',
            $event->getValues()[0]['after']['j']
        );
    }

    public function testMysql8JsonReplacePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || BinLogServerInfo::isMariaDb()) {
            $this->markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->exec('UPDATE t1 SET j = JSON_REPLACE(j, \'$.addr.detail.ab\', \'9707\')');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var UpdateRowsDTO $event */
        $event = $this->getEvent();

        self::assertInstanceOf(UpdateRowsDTO::class, $event);
        self::assertEquals(
            $expected,
            $event->getValues()[0]['before']['j']
        );
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{"ab":"9707"}},"name":"Alice"}',
            $event->getValues()[0]['after']['j']
        );

    }
}