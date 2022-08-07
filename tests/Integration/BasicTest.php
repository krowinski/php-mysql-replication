<?php
/** @noinspection PhpPossiblePolymorphicInvocationInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use MySQLReplication\MySQLReplicationFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

        $this->connection->executeStatement('DELETE FROM test WHERE id = 1');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var DeleteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(DeleteRowsDTO::class, $event);
        self::assertEquals(1, $event->getValues()[0]['id']);
        self::assertEquals('Hello World', $event->getValues()[0]['data']);
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

        $this->connection->executeStatement('UPDATE test SET data = \'World\', id = 2 WHERE id = 1');

        self::assertInstanceOf(XidDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var UpdateRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(UpdateRowsDTO::class, $event);
        self::assertEquals(1, $event->getValues()[0]['before']['id']);
        self::assertEquals('Hello', $event->getValues()[0]['before']['data']);
        self::assertEquals(2, $event->getValues()[0]['after']['id']);
        self::assertEquals('World', $event->getValues()[0]['after']['data']);
    }

    /**
     * @test
     */
    public function shouldGetWriteEventDropTable(): void
    {
        $this->connection->executeStatement($createExpected = 'CREATE TABLE `test` (id INTEGER(11))');
        $this->connection->executeStatement('INSERT INTO `test` VALUES (1)');
        $this->connection->executeStatement($dropExpected = 'DROP TABLE `test`');

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
        self::assertStringContainsString($dropExpected, $event->getQuery());
    }

    /**
     * @test
     */
    public function shouldGetQueryEventCreateTable(): void
    {
        $this->connection->executeStatement(
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

        $this->connection->executeStatement('CREATE TABLE test_drop_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (1, \'A value\')');
        $this->connection->executeStatement('ALTER TABLE test_drop_column DROP COLUMN data');
        $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (2)');

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

        $this->connection->executeStatement($createTableExpected = 'CREATE TABLE test (id INTEGER(11), data VARCHAR(50))');

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

        $this->connection->executeStatement(
            'CREATE TABLE test_2 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );
        $this->connection->executeStatement(
            'CREATE TABLE test_3 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );
        $this->connection->executeStatement(
            'CREATE TABLE test_4 (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        $this->connection->executeStatement('INSERT INTO test_4 (data) VALUES (\'foo\')');
        $this->connection->executeStatement('INSERT INTO test_3 (data) VALUES (\'bar\')');
        $this->connection->executeStatement('INSERT INTO test_2 (data) VALUES (\'' . $expectedValue . '\')');

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

        $this->connection->executeStatement('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->executeStatement('INSERT INTO test_truncate_column VALUES (1, \'A value\')');
        $this->connection->executeStatement('TRUNCATE TABLE test_truncate_column');

        $event = $this->getEvent();
        self::assertSame('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))', $event->getQuery());
        $event = $this->getEvent();
        self::assertSame('BEGIN', $event->getQuery());
        $event = $this->getEvent();
        self::assertSame('TRUNCATE TABLE test_truncate_column', $event->getQuery());
    }

    /**
     * @test
     */
    public function shouldJsonSetPartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->getBinLogServerInfo()->isMariaDb()) {
            self::markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8 - C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->executeQuery('UPDATE t1 SET j = JSON_SET(j, \'$.addr.detail.ab\', \'970785C8\')');

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

    /**
     * @test
     */
    public function shouldJsonRemovePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->getBinLogServerInfo()->isMariaDb()) {
            self::markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->executeStatement('UPDATE t1 SET j = JSON_REMOVE(j, \'$.addr.detail.ab\')');

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

    /**
     * @test
     */
    public function shouldJsonReplacePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->getBinLogServerInfo()->isMariaDb()) {
            self::markTestIncomplete('Only for mysql 5.7 or higher');
        }

        $expected = '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8-C299"}},"name":"Alice"}';

        $create_query = 'CREATE TABLE t1 (j JSON)';
        $insert_query = "INSERT INTO t1 VALUES ('" . $expected . "')";

        $this->createAndInsertValue($create_query, $insert_query);

        $this->connection->executeStatement('UPDATE t1 SET j = JSON_REPLACE(j, \'$.addr.detail.ab\', \'9707\')');

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

    /**
     * @test
     */
    public function shouldRoteLog(): void
    {
        $this->connection->executeStatement('FLUSH LOGS');

        self::assertInstanceOf(RotateDTO::class, $this->getEvent());

        self::assertMatchesRegularExpression(
            '/^[a-z-]+\.[\d]+$/',
            $this->getEvent()->getEventInfo()->getBinLogCurrent()->getBinFileName()
        );
    }

    /**
     * @test
     */
    public function shouldUseProvidedEventDispatcher(): void
    {
        $this->disconnect();

        $testEventSubscribers = new TestEventSubscribers($this);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber($testEventSubscribers);

        $this->connectWithProvidedEventDispatcher($eventDispatcher);

        $this->connection->executeStatement(
            $createExpected = 'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createExpected, $event->getQuery());
    }

    private function connectWithProvidedEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory(
            $this->configBuilder->build(),
            null,
            null,
            $eventDispatcher
        );

        $this->connection = $this->mySQLReplicationFactory->getDbConnection();

        $this->connection->executeStatement('SET SESSION time_zone = "+00:00"');
        $this->connection->executeStatement('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->executeStatement('CREATE DATABASE ' . $this->database);
        $this->connection->executeStatement('USE ' . $this->database);
        $this->connection->executeStatement('SET SESSION sql_mode = \'\';');

        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
    }
}
