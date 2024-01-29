<?php

declare(strict_types=1);

namespace MySQLReplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Cache\ArrayCache;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\Event;
use MySQLReplication\Event\RowEvent\RowEventBuilder;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Repository\RepositoryInterface;
use MySQLReplication\Socket\Socket;
use MySQLReplication\Socket\SocketInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MySQLReplicationFactory
{
    private ?Connection $connection = null;
    private EventDispatcherInterface $eventDispatcher;
    private Event $event;
    private BinLogSocketConnect $binLogSocketConnect;

    public function __construct(
        Config $config,
        RepositoryInterface $repository = null,
        CacheInterface $cache = null,
        EventDispatcherInterface $eventDispatcher = null,
        SocketInterface $socket = null,
        LoggerInterface $logger = null
    ) {
        $config->validate();

        if ($repository === null) {
            $this->connection = DriverManager::getConnection(
                [
                    'user' => $config->user,
                    'password' => $config->password,
                    'host' => $config->host,
                    'port' => $config->port,
                    'driver' => 'pdo_mysql',
                    'charset' => $config->charset,
                ]
            );
            $repository = new MySQLRepository($this->connection);
        }

        $cache = $cache ?: new ArrayCache($config->tableCacheSize);
        $logger = $logger ?: new NullLogger();
        $socket = $socket ?: new Socket();

        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();

        $this->binLogSocketConnect = new BinLogSocketConnect($repository, $socket, $logger, $config);

        $this->event = new Event(
            $this->binLogSocketConnect,
            new RowEventFactory(
                new RowEventBuilder(
                    $repository,
                    $cache,
                    $config,
                    $this->binLogSocketConnect->getBinLogServerInfo(),
                    $logger
                )
            ),
            $this->eventDispatcher,
            $cache,
            $config,
            $this->binLogSocketConnect->getBinLogServerInfo()
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

    public function getDbConnection(): ?Connection
    {
        return $this->connection;
    }

    public function run(): void
    {
        /** @phpstan-ignore-next-line */
        while (1) {
            $this->consume();
        }
    }

    public function consume(): void
    {
        $this->event->consume();
    }

    public function getServerInfo(): BinLogServerInfo
    {
        return $this->binLogSocketConnect->getBinLogServerInfo();
    }
}
