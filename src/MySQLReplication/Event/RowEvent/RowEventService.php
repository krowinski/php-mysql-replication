<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderFactory;
use MySQLReplication\Repository\MySQLRepository;

/**
 * Class RowEventService
 * @package MySQLReplication\RowEvent
 */
class RowEventService
{
    /**
     * RowEventService constructor.
     * @param Config $config
     * @param MySQLRepository $mySQLRepository
     * @param JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
     */
    public function __construct(
        Config $config,
        MySQLRepository $mySQLRepository,
        JsonBinaryDecoderFactory $jsonBinaryDecoderFactory
    )
    {
        $this->rowEventBuilder = new RowEventBuilder($config, $mySQLRepository, $jsonBinaryDecoderFactory);
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