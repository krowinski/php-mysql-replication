<?php
declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class RowEventBuilder
 * @package MySQLReplication\BinaryDataReader
 */
class RowEventBuilder
{
    /**
     * @var BinaryDataReader
     */
    private $package;
    /**
     * @var RepositoryInterface
     */
    private $repository;
    /**
     * @var EventInfo
     */
    private $eventInfo;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * RowEventBuilder constructor.
     * @param RepositoryInterface $repository
     * @param CacheInterface $cache
     */
    public function __construct(
        RepositoryInterface $repository,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * @param BinaryDataReader $package
     */
    public function withPackage(BinaryDataReader $package): void
    {
        $this->package = $package;
    }

    /**
     * @return RowEvent
     */
    public function build(): RowEvent
    {
        return new RowEvent(
            $this->repository,
            $this->package,
            $this->eventInfo,
            $this->cache
        );
    }

    /**
     * @param EventInfo $eventInfo
     */
    public function withEventInfo(EventInfo $eventInfo): void
    {
        $this->eventInfo = $eventInfo;
    }
}