<?php

namespace MySQLReplication\Tests\Integration;

use Doctrine\DBAL\Connection;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Class BaseTest
 * @package MySQLReplication\Unit
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @param EventDTO $eventDTO
     */
    public function setEvent(EventDTO $eventDTO)
    {
        $this->currentEvent = $eventDTO;
    }

    protected function setUp()
    {
        parent::setUp();

        $this->configBuilder = (new ConfigBuilder())
            ->withUser('root')
            ->withHost('127.0.0.1')
            ->withPassword('root')
            ->withEventsIgnore([ConstEventType::GTID_LOG_EVENT]);

        $this->connect();

        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

    }

    public function connect()
    {
        $this->mySQLReplicationFactory = new MySQLReplicationFactory($this->configBuilder->build());
        $this->testEventSubscribers = new TestEventSubscribers($this);
        $this->mySQLReplicationFactory->registerSubscriber($this->testEventSubscribers);

        $this->connection = $this->mySQLReplicationFactory->getDbConnection();

        $this->connection->exec('SET SESSION time_zone = "UTC"');
        $this->connection->exec('DROP DATABASE IF EXISTS ' . $this->database);
        $this->connection->exec('CREATE DATABASE ' . $this->database);
        $this->connection->exec('USE ' . $this->database);
        $this->connection->exec('SET SESSION sql_mode = \'\';');
    }

    protected function disconnect()
    {
        $this->mySQLReplicationFactory->unregisterSubscriber($this->testEventSubscribers);
        $this->mySQLReplicationFactory = null;
        $this->connection = null;
    }

    /**
     * @return EventDTO
     */
    protected function getEvent()
    {
        // events can be null lets us continue until we find event
        $this->currentEvent = null;
        while (1) {
            $this->mySQLReplicationFactory->consume();
            if (null !== $this->currentEvent) {
                return $this->currentEvent;
            }
        }
        return $this->currentEvent;
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->disconnect();
    }

    /**
     * @param string $createQuery
     * @param string $insertQuery
     * @return \MySQLReplication\Event\DTO\DeleteRowsDTO|\MySQLReplication\Event\DTO\EventDTO|\MySQLReplication\Event\DTO\GTIDLogDTO|\MySQLReplication\Event\DTO\QueryDTO|\MySQLReplication\Event\DTO\RotateDTO|\MySQLReplication\Event\DTO\TableMapDTO|\MySQLReplication\Event\DTO\UpdateRowsDTO|\MySQLReplication\Event\DTO\WriteRowsDTO|\MySQLReplication\Event\DTO\XidDTO
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createAndInsertValue($createQuery, $insertQuery)
    {
        $this->connection->exec($createQuery);
        $this->connection->exec($insertQuery);

        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(TableMapDTO::class, $this->getEvent());

        return $this->getEvent();
    }
}