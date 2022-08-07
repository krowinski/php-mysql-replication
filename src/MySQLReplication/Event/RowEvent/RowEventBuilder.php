<?php
declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\SimpleCache\CacheInterface;

class RowEventBuilder
{
    private $repository;
    private $cache;
    /**
     * @var BinaryDataReader
     */
    private $package;
    /**
     * @var EventInfo
     */
    private $eventInfo;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config,
        RepositoryInterface $repository,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function withPackage(BinaryDataReader $package): void
    {
        $this->package = $package;
    }

    public function build(): RowEvent
    {
        return new RowEvent(
            $this->config,
            $this->repository,
            $this->package,
            $this->eventInfo,
            $this->cache
        );
    }

    public function withEventInfo(EventInfo $eventInfo): void
    {
        $this->eventInfo = $eventInfo;
    }
}