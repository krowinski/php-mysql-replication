<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class RowEventBuilder
{
    private BinaryDataReader $binaryDataReader;
    private EventInfo $eventInfo;

    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly CacheInterface $cache,
        private readonly Config $config,
        private readonly BinLogServerInfo $binLogServerInfo,
        private readonly LoggerInterface $logger
    ) {
    }

    public function withBinaryDataReader(BinaryDataReader $binaryDataReader): void
    {
        $this->binaryDataReader = $binaryDataReader;
    }

    public function build(): RowEvent
    {
        return new RowEvent(
            $this->repository,
            $this->binaryDataReader,
            $this->eventInfo,
            new TableMapCache($this->cache),
            $this->config,
            $this->binLogServerInfo,
            $this->logger
        );
    }

    public function withEventInfo(EventInfo $eventInfo): void
    {
        $this->eventInfo = $eventInfo;
    }
}
