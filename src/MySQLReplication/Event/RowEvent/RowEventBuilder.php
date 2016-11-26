<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\RepositoryInterface;

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
     * @var Config
     */
    private $config;
    /**
     * @var EventInfo
     */
    private $eventInfo;
    /**
     * @var JsonBinaryDecoderFactory
     */
    private $jsonBinaryDecoderFactory;

    /**
     * RowEventBuilder constructor.
     * @param Config $config
     * @param RepositoryInterface $repository
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     */
    public function __construct(
        Config $config,
        RepositoryInterface $repository,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
    )
    {
        $this->repository = $repository;
        $this->config = $config;
        $this->jsonBinaryDecoderFactory = $jsonBinaryDecoderFactory;
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
            $this->config,
            $this->repository,
            $this->package,
            $this->eventInfo,
            $this->jsonBinaryDecoderFactory
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