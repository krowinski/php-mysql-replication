<?php
declare(strict_types=1);

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

class MySQLReplicationFactory
{
    private $connection;
    private $eventDispatcher;
    private $event;

    /**
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
        $config::validate();

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

    public function registerSubscriber(EventSubscriberInterface $eventSubscribers): void
    {
        $this->eventDispatcher->addSubscriber($eventSubscribers);
    }

    public function unregisterSubscriber(EventSubscriberInterface $eventSubscribers): void
    {
        $this->eventDispatcher->removeSubscriber($eventSubscribers);
    }

    public function getDbConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @throws SocketException
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     * @throws BinLogException
     * @throws InvalidArgumentException
     * @throws MySQLReplicationException
     */
    public function run(): void
    {
        while (1) {
            $this->consume();
        }
    }

    /**
     * @throws MySQLReplicationException
     * @throws InvalidArgumentException
     * @throws BinLogException
     * @throws BinaryDataReaderException
     * @throws JsonBinaryDecoderException
     * @throws SocketException
     */
    public function consume(): void
    {
        $this->event->consume();
    }
}