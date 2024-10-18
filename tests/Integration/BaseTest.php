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
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\MySQLReplicationFactory;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @var MySQLReplicationFactory
     */
    protected $mySQLReplicationFactory;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var string
     */
    protected $database = 'mysqlreplication_test';
    /**
     * @var EventDTO
     */
    protected $currentEvent;
    /**
     * @var ConfigBuilder
     */
    protected $configBuilder;
    /**
     * @var TestEventSubscribers
     */
    private $testEventSubscribers;

    public function setEvent(EventDTO $eventDTO): void
    {
        $this->currentEvent = $eventDTO;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configBuilder = (new ConfigBuilder())
            ->withUser('root')
            ->withHost('127.0.0.1')
            ->withPassword('root')
            ->withPort(3306)
            ->withEventsIgnore([ConstEventType::GTID_LOG_EVENT]);

        $this->connect();

        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
    }

    public function connect(): void
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory($this->configBuilder->build());
        $this->testEventSubscribers = new TestEventSubscribers($this);
        $this->mySQLReplicationFactory->registerSubscriber($this->testEventSubscribers);

        $this->connection = $this->mySQLReplicationFactory->getDbConnection();

        $this->connection->executeStatement('SET SESSION time_zone = "UTC"');
        $this->connection->executeStatement('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->executeStatement('CREATE DATABASE ' . $this->database);
        $this->connection->executeStatement('USE ' . $this->database);
        $this->connection->executeStatement('SET SESSION sql_mode = \'\';');
    }

    protected function getEvent(): EventDTO
    {
        // events can be null lets us continue until we find event
        $this->currentEvent = null;
        while (1) {
            $this->mySQLReplicationFactory->consume();
            if (null !== $this->currentEvent) {
                return $this->currentEvent;
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->disconnect();
    }

    protected function disconnect(): void
    {
        $this->mySQLReplicationFactory->unregisterSubscriber($this->testEventSubscribers);
        $this->mySQLReplicationFactory = null;
        $this->connection = null;
    }

    protected function checkForVersion(float $version): bool
    {
        return (float)$this->connection->fetchOne('SELECT VERSION()') < $version;
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
