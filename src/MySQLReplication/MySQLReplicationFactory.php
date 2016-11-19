<?php

namespace MySQLReplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use MySQLReplication\BinLog\BinLogAuth;
use MySQLReplication\BinLog\BinLogConnect;
use MySQLReplication\BinLog\Exception\BinLogException;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\Exception\ConfigException;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\Event\RowEvent\RowEventService;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidService;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderService;
use MySQLReplication\Repository\MySQLRepository;
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
     * @var MySQLRepository
     */
    private $MySQLRepository;
    /**
     * @var BinLogConnect
     */
    private $binLogConnect;
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
    private $GtiService;

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
        $this->binLogAuth = new BinLogAuth();
        $this->MySQLRepository = new MySQLRepository($this->connection);
        $this->GtiService = new GtidService();

        $this->binLogConnect = new BinLogConnect($config, $this->MySQLRepository, $this->binLogAuth, $this->GtiService);
        $this->binLogConnect->connectToStream();

        $this->jsonBinaryDecoderFactory = new JsonBinaryDecoderFactory();
        $this->binaryDataReaderService = new BinaryDataReaderService();
        $this->rowEventService = new RowEventService($config, $this->MySQLRepository, $this->jsonBinaryDecoderFactory);
        $this->eventDispatcher = new EventDispatcher();

        $this->event = new Event(
            $config,
            $this->binLogConnect,
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
