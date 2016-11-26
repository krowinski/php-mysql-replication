<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\RepositoryInterface;

/**
 * Class RowEventService
 * @package MySQLReplication\RowEvent
 */
class RowEventService
{
    /**
     * @var RowEventBuilder
     */
    private $rowEventBuilder;

    /**
     * RowEventService constructor.
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
        $this->rowEventBuilder = new RowEventBuilder(
            $config,
            $repository,
            $jsonBinaryDecoderFactory
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