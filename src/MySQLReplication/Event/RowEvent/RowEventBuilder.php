<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
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
     * @var JsonBinaryDecoderFactory
     */
    private $jsonBinaryDecoderFactory;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * RowEventBuilder constructor.
     * @param RepositoryInterface $repository
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        RepositoryInterface $repository,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->jsonBinaryDecoderFactory = $jsonBinaryDecoderFactory;
        $this->cache = $cache;
    }

    /**
     * @param BinaryDataReader $package
     */
    public function withPackage(BinaryDataReader $package)
    {
        $this->package = $package;
    }

    /**
     * @return RowEvent
     */
    public function build()
    {
        return new RowEvent(
            $this->repository,
            $this->package,
            $this->eventInfo,
            $this->jsonBinaryDecoderFactory,
            $this->cache
        );
    }

    /**
     * @param EventInfo $eventInfo
     */
    public function withEventInfo(EventInfo $eventInfo)
    {
        $this->eventInfo = $eventInfo;
    }
}