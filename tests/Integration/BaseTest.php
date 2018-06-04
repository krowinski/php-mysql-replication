<?php

namespace MySQLReplication\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\GTIDLogDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidException;
use MySQLReplication\MySQLReplicationFactory;
use MySQLReplication\Socket\SocketException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class BaseTest
 * @package MySQLReplication\Unit
 */
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

    /**
     * @param EventDTO $eventDTO
     */
    public function setEvent(EventDTO $eventDTO)
    {
        $this->currentEvent = $eventDTO;
    }

    /**
     * @throws BinLogException
     * @throws ConfigException
     * @throws DBALException
     * @throws GtidException
     * @throws InvalidArgumentException
     * @throws MySQLReplicationException
     * @throws SocketException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configBuilder = (new ConfigBuilder())
            ->withUser('root')
            ->withHost('127.0.0.1')
            ->withPassword('root')
            ->withPort(3333)
            ->withEventsIgnore([ConstEventType::GTID_LOG_EVENT]);

        $this->connect();

        self::assertInstanceOf(FormatDescriptionEventDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

    }

    /**
     * @throws BinLogException
     * @throws DBALException
     * @throws ConfigException
     * @throws GtidException
     * @throws SocketException
     */
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

    /**
     * @return EventDTO
     * @throws MySQLReplicationException
     * @throws InvalidArgumentException
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

    protected function disconnect()
    {
        $this->mySQLReplicationFactory->unregisterSubscriber($this->testEventSubscribers);
        $this->mySQLReplicationFactory = null;
        $this->connection = null;
    }

    /**
     * @param string $createQuery
     * @param string $insertQuery
     * @return DeleteRowsDTO|EventDTO|GTIDLogDTO|QueryDTO|RotateDTO|TableMapDTO|UpdateRowsDTO|WriteRowsDTO|XidDTO
     * @throws InvalidArgumentException
     * @throws MySQLReplicationException
     * @throws DBALException
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