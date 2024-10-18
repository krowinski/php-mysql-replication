<?php
declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\SimpleCache\CacheInterface;

class RowEventFactory
{
    private $rowEventBuilder;

    public function __construct(
        RepositoryInterface $repository,
        CacheInterface $cache
    ) {
        $this->rowEventBuilder = new RowEventBuilder($repository, $cache);
    }

    public function makeRowEvent(BinaryDataReader $package, EventInfo $eventInfo): RowEvent
    {
        $this->rowEventBuilder->withPackage($package);
        $this->rowEventBuilder->withEventInfo($eventInfo);

        return $this->rowEventBuilder->build();
    }
}