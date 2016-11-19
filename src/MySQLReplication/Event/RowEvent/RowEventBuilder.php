<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\MySQLRepository;

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
     * @var MySQLRepository
     */
    private $MySQLRepository;
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
     * @param MySQLRepository $MySQLRepository
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     */
    public function __construct(
        Config $config,
        MySQLRepository $MySQLRepository,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
    )
    {
        $this->MySQLRepository = $MySQLRepository;
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
            $this->MySQLRepository,
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