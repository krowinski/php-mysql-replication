<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use Doctrine\DBAL\Connection;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\MySQLReplicationFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class BaseCase extends TestCase
{
    protected ?MySQLReplicationFactory $mySQLReplicationFactory;

    protected Connection $connection;

    protected string $database = 'mysqlreplication_test';

    protected ?EventDTO $currentEvent;

    protected ConfigBuilder $configBuilder;

    private TestEventSubscribers $testEventSubscribers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configBuilder = (new ConfigBuilder())
            ->withUser('root')
            ->withHost('0.0.0.0')
            ->withPassword('root')
            ->withPort(3306)
            ->withEventsIgnore($this->getIgnoredEvents());

        $this->connect();

        var_dump($this->mySQLReplicationFactory?->getServerInfo());

        if ($this->mySQLReplicationFactory?->getServerInfo()->versionRevision >= 8 && $this->mySQLReplicationFactory?->getServerInfo()->isGeneric()) {
            self::assertInstanceOf(RotateDTO::class, $this->getEvent());
        }
        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->disconnect();
    }

    public function setEvent(EventDTO $eventDTO): void
    {
        $this->currentEvent = $eventDTO;
    }

    public function connect(): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory($this->configBuilder->build());
        $this->testEventSubscribers = new TestEventSubscribers($this);
        $this->mySQLReplicationFactory->registerSubscriber($this->testEventSubscribers);

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
    }

    protected function getIgnoredEvents(): array
    {
        return [
            ConstEventType::GTID_LOG_EVENT->value, // Generally in here
            ConstEventType::ROWS_QUERY_LOG_EVENT->value, // Just debugging, there is a special test for it
        ];
    }

    protected function getEvent(): EventDTO
    {
        if ($this->mySQLReplicationFactory === null) {
            throw new RuntimeException('MySQLReplicationFactory not initialized');
        }

        // events can be null lets us continue until we find event
        $this->currentEvent = null;
        while ($this->currentEvent === null) {
            $this->mySQLReplicationFactory->consume();
        }
        /** @phpstan-ignore-next-line */
        return $this->currentEvent;
    }

    protected function disconnect(): void
    {
        if ($this->mySQLReplicationFactory === null) {
            return;
        }
        $this->mySQLReplicationFactory->unregisterSubscriber($this->testEventSubscribers);
        $this->mySQLReplicationFactory = null;
    }

    protected function checkForVersion(float $version): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->mySQLReplicationFactory->getServerInfo()
                ->versionRevision < $version;
    }

    protected function createAndInsertValue(string $createQuery, string $insertQuery): EventDTO
    {
        $this->connection->executeStatement($createQuery);
        $this->connection->executeStatement($insertQuery);

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        return $this->getEvent();
    }
}
