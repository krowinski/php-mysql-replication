<?php

namespace MySQLReplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use MySQLReplication\BinLog\BinLogAuth;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\BinLog\Exception\BinLogException;
use MySQLReplication\BinLog\SocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\Exception\ConfigException;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\Event\RowEvent\RowEventService;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidService;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Repository\Repository;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class MySQLReplicationFactory
 * @package MySQLReplication
 */
class MySQLReplicationFactory
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var SocketConnect
     */
    private $socketConnect;
    /**
     * @var Event
     */
    private $event;
    /**
     * @var BinLogAuth
     */
    private $binLogAuth;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var BinaryDataReaderService
     */
    private $binaryDataReaderService;
    /**
     * @var GtidService
     */
    private $gtiService;
    /**
     * @var RowEventService
     */
    private $rowEventService;
    /**
     * @var JsonBinaryDecoderFactory
     */
    private $jsonBinaryDecoderFactory;

    /**
     * @param Config $config
     * @throws MySQLReplicationException
     * @throws DBALException
     * @throws ConfigException
     * @throws BinLogException
     */
    public function __construct(Config $config)
    {
        $config->validate();

        $this->connection = DriverManager::getConnection([
            'user' => $config->getUser(),
            'password' => $config->getPassword(),
            'host' => $config->getHost(),
            'port' => $config->getPort(),
            'driver' => 'pdo_mysql',
            'charset' => $config->getCharset()
        ]);
        $this->repository = new MySQLRepository($this->connection);
        $this->gtiService = new GtidService();
        $this->binLogAuth = new BinLogAuth();

        $this->socketConnect = new BinLogSocketConnect(
            $config,
            $this->repository,
            $this->binLogAuth,
            $this->gtiService
        );
        $this->socketConnect->connectToStream();

        $this->jsonBinaryDecoderFactory = new JsonBinaryDecoderFactory();
        $this->rowEventService = new RowEventService(
            $config,
            $this->repository,
            $this->jsonBinaryDecoderFactory
        );
        $this->binaryDataReaderService = new BinaryDataReaderService();
        $this->eventDispatcher = new EventDispatcher();

        $this->event = new Event(
            $config,
            $this->socketConnect,
            $this->binaryDataReaderService,
            $this->rowEventService,
            $this->eventDispatcher
        );
    }

    /**
     * @param EventSubscribers $eventSubscribers
     */
    public function registerSubscriber(EventSubscribers $eventSubscribers)
    {
        $this->eventDispatcher->addSubscriber($eventSubscribers);
    }

    /**
     * @return Connection
     */
    public function getDbConnection()
    {
        return $this->connection;
    }

    /**
     * @throws MySQLReplicationException
     */
    public function binLogEvent()
    {
        $this->event->consume();
    }
}
