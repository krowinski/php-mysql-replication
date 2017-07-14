<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class RowEventService
 * @package MySQLReplication\RowEvent
 */
class RowEventFactory
{
    /**
     * @var RowEventBuilder
     */
    private $rowEventBuilder;

    /**
     * RowEventService constructor.
     * @param RepositoryInterface $repository
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        RepositoryInterface $repository,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory,
        CacheInterface $cache
    ) {
        $this->rowEventBuilder = new RowEventBuilder(
            $repository,
            $jsonBinaryDecoderFactory,
            $cache
        );
    }

    /**
     * @param BinaryDataReader $package
     * @param EventInfo $eventInfo
     * @return RowEvent
     */
    public function makeRowEvent(BinaryDataReader $package, EventInfo $eventInfo)
    {
        $this->rowEventBuilder->withPackage($package);
        $this->rowEventBuilder->withEventInfo($eventInfo);

        return $this->rowEventBuilder->build();
    }
}