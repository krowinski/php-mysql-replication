<?php

namespace MySQLReplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\EventException;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\Gtid\GtidException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Repository\RepositoryInterface;
use MySQLReplication\Socket\Socket;
use MySQLReplication\Socket\SocketException;
use MySQLReplication\Socket\SocketInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MySQLReplicationFactory
 * @package MySQLReplication
 */
class MySQLReplicationFactory
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var Event
     */
    private $event;

    /**
     * @param Config $config
     * @param RepositoryInterface|null $repository
     * @param CacheInterface|null $cache
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param SocketInterface|null $socket
     * @throws BinLogException
     * @throws ConfigException
     * @throws DBALException
     * @throws SocketException
     * @throws GtidException
     */
    public function __construct(
        Config $config,
        RepositoryInterface $repository = null,
        CacheInterface $cache = null,
        EventDispatcherInterface $eventDispatcher = null,
        SocketInterface $socket = null
    ) {
        Config::validate();

        if (null === $repository) {
            $this->connection = DriverManager::getConnection(
                [
                    'user' => Config::getUser(),
                    'password' => Config::getPassword(),
                    'host' => Config::getHost(),
                    'port' => Config::getPort(),
                    'driver' => 'pdo_mysql',
                    'charset' => Config::getCharset()
                ]
            );
            $repository = new MySQLRepository($this->connection);
        }
        if (null === $cache) {
            $cache = new ArrayCache();
        }
        if (null === $eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }
        if (null === $socket) {
            $socket = new Socket();
        }

        $this->event = new Event(
            new BinLogSocketConnect(
                $repository,
                $socket
            ),
            new RowEventFactory(
                $repository,
                $cache
            ),
            $this->eventDispatcher,
            $cache
        );
    }

    /**
     * @param EventSubscriberInterface $eventSubscribers
     */
    public function registerSubscriber(EventSubscriberInterface $eventSubscribers)
    {
        $this->eventDispatcher->addSubscriber($eventSubscribers);
    }

    /**
     * @param EventSubscriberInterface $eventSubscribers
     */
    public function unregisterSubscriber(EventSubscriberInterface $eventSubscribers)
    {
        $this->eventDispatcher->removeSubscriber($eventSubscribers);
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
     * @throws InvalidArgumentException
     * @throws BinLogException
     * @throws BinaryDataReaderException
     * @throws EventException
     * @throws JsonBinaryDecoderException
     * @throws SocketException
     */
    public function consume()
    {
        $this->event->consume();
    }

    /**
     * @throws SocketException
     * @throws JsonBinaryDecoderException
     * @throws EventException
     * @throws BinaryDataReaderException
     * @throws BinLogException
     * @throws InvalidArgumentException
     * @throws MySQLReplicationException
     */
    public function run()
    {
        while (1) {
            $this->consume();
        }
    }
}