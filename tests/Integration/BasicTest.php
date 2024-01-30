<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

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
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicTest extends BaseCase
{
    public function testShouldGetDeleteEvent(): void
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
        self::assertEquals(1, $event->values[0]['id']);
        self::assertEquals('Hello World', $event->values[0]['data']);
    }

    public function testShouldGetUpdateEvent(): void
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
        self::assertEquals(1, $event->values[0]['before']['id']);
        self::assertEquals('Hello', $event->values[0]['before']['data']);
        self::assertEquals(2, $event->values[0]['after']['id']);
        self::assertEquals('World', $event->values[0]['after']['data']);
    }

    public function testShouldGetWriteEventDropTable(): void
    {
        $this->connection->executeStatement($createExpected = 'CREATE TABLE `test` (id INTEGER(11))');
        $this->connection->executeStatement('INSERT INTO `test` VALUES (1)');
        $this->connection->executeStatement($dropExpected = 'DROP TABLE `test`');

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createExpected, $event->query);

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals('BEGIN', $event->query);

        /** @var TableMapDTO $event */
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        /** @var WriteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals([], $event->values);
        self::assertEquals(0, $event->changedRows);

        self::assertInstanceOf(XidDTO::class, $this->getEvent());

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertStringContainsString($dropExpected, $event->query);
    }

    public function testShouldGetQueryEventCreateTable(): void
    {
        $this->connection->executeStatement(
            $createExpected = 'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createExpected, $event->query);
    }

    public function testShouldDropColumn(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly(
            [ConstEventType::WRITE_ROWS_EVENT_V1->value, ConstEventType::WRITE_ROWS_EVENT_V2->value]
        );

        $this->connect();

        $this->connection->executeStatement('CREATE TABLE test_drop_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (1, \'A value\')');
        $this->connection->executeStatement('ALTER TABLE test_drop_column DROP COLUMN data');
        $this->connection->executeStatement('INSERT INTO test_drop_column VALUES (2)');

        /** @var WriteRowsDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals([
            'id' => 1,
            'DROPPED_COLUMN_1' => null,
        ], $event->values[0]);

        $event = $this->getEvent();
        self::assertInstanceOf(WriteRowsDTO::class, $event);
        self::assertEquals([
            'id' => 2,
        ], $event->values[0]);
    }

    public function testShouldFilterEvents(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly([ConstEventType::QUERY_EVENT->value]);

        $this->connect();

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $this->connection->executeStatement(
            $createTableExpected = 'CREATE TABLE test (id INTEGER(11), data VARCHAR(50))'
        );

        /** @var QueryDTO $event */
        $event = $this->getEvent();
        self::assertInstanceOf(QueryDTO::class, $event);
        self::assertEquals($createTableExpected, $event->query);
    }

    public function testShouldFilterTables(): void
    {
        $expectedTable = 'test_2';
        $expectedValue = 'foobar';

        $this->disconnect();

        $this->configBuilder
            ->withEventsOnly(
                [ConstEventType::WRITE_ROWS_EVENT_V1->value, ConstEventType::WRITE_ROWS_EVENT_V2->value]
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
        self::assertEquals($expectedTable, $event->tableMap->table);
        self::assertEquals($expectedValue, $event->values[0]['data']);
    }

    public function testShouldTruncateTable(): void
    {
        $this->disconnect();

        $this->configBuilder->withEventsOnly([ConstEventType::QUERY_EVENT->value]);

        $this->connect();

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $this->connection->executeStatement('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))');
        $this->connection->executeStatement('INSERT INTO test_truncate_column VALUES (1, \'A value\')');
        $this->connection->executeStatement('TRUNCATE TABLE test_truncate_column');

        $event = $this->getEvent();
        self::assertSame('CREATE TABLE test_truncate_column (id INTEGER(11), data VARCHAR(50))', $event->query);
        $event = $this->getEvent();
        self::assertSame('BEGIN', $event->query);
        $event = $this->getEvent();
        self::assertSame('TRUNCATE TABLE test_truncate_column', $event->query);
    }

    public function testShouldJsonSetPartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->mySQLReplicationFactory?->getServerInfo()->isMariaDb()) {
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
        self::assertEquals($expected, $event->values[0]['before']['j']);
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{"ab":"970785C8"}},"name":"Alice"}',
            $event->values[0]['after']['j']
        );
    }

    public function testShouldJsonRemovePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->mySQLReplicationFactory?->getServerInfo()->isMariaDb()) {
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
        self::assertEquals($expected, $event->values[0]['before']['j']);
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{}},"name":"Alice"}',
            $event->values[0]['after']['j']
        );
    }

    public function testShouldJsonReplacePartialUpdateWithHoles(): void
    {
        if ($this->checkForVersion(5.7) || $this->mySQLReplicationFactory?->getServerInfo()->isMariaDb()) {
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
        self::assertEquals($expected, $event->values[0]['before']['j']);
        self::assertEquals(
            '{"age":22,"addr":{"code":100,"detail":{"ab":"9707"}},"name":"Alice"}',
            $event->values[0]['after']['j']
        );
    }

    public function testShouldRotateLog(): void
    {
        $this->connection->executeStatement('FLUSH LOGS');

        self::assertInstanceOf(RotateDTO::class, $this->getEvent());

        self::assertMatchesRegularExpression(
            '/^[a-z-]+\.[\d]+$/',
            $this->getEvent()
                ->getEventInfo()
                ->binLogCurrent
                ->getBinFileName()
        );
    }

    public function testShouldUseProvidedEventDispatcher(): void
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
        self::assertEquals($createExpected, $event->query);
    }

    private function connectWithProvidedEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory(
            $this->configBuilder->build(),
            null,
            null,
            $eventDispatcher
        );

        $connection = $this->mySQLReplicationFactory->getDbConnection();
        if ($connection === null) {
            throw new RuntimeException('Connection not initialized');
        }

        $this->connection = $connection;
        $this->connection->executeStatement('SET SESSION time_zone = "UTC"');
        $this->connection->executeStatement('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->executeStatement('CREATE DATABASE ' . $this->database);
        $this->connection->executeStatement('USE ' . $this->database);
        $this->connection->executeStatement('SET SESSION sql_mode = \'\';');

        if ($this->mySQLReplicationFactory->getServerInfo()->versionRevision >= 8 && $this->mySQLReplicationFactory->getServerInfo()->isGeneric()) {
            self::assertInstanceOf(RotateDTO::class, $this->getEvent());
        }

        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
    }
}
