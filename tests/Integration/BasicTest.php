<?php

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;

/**
 * Class BasicTest
 * @package MySQLReplication\Tests\Integration
 */
class BasicTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldGetDeleteEvent()
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
    public function shouldGetUpdateEvent()
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
    public function shouldGetWriteEventDropTable()
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
    public function shouldGetQueryEventCreateTable()
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
    public function shouldDropColumn()
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
    public function shouldFilterEvents()
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
    public function shouldFilterTables()
    {
        $expectedTable = 'test_2';
        $expectedValue = 'foobar';

        $this->disconnect();

        $this->configBuilder
            ->withEventsOnly(
                [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2]
            )->withTablesOnly([$expectedTable]);

        $this->connect();

        $this->connection->exec('CREATE TABLE test_2 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))');
        $this->connection->exec('CREATE TABLE test_3 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))');
        $this->connection->exec('CREATE TABLE test_4 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))');

        $this->connection->exec('INSERT INTO test_4 (data) VALUES (\'foo\')');
        $this->connection->exec('INSERT INTO test_3 (data) VALUES (\'bar\')');
        $this->connection->exec('INSERT INTO test_2 (data) VALUES (\''. $expectedValue .'\')');

        /** @var WriteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals($expectedTable, $event->getTableMap()->getTable());
        self::assertEquals($expectedValue, $event->getValues()[0]['data']);
    }
}