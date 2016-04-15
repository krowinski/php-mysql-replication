<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
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

    public function __construct(
        Config $config,
        MySQLRepository $MySQLRepository
    )
    {
        $this->MySQLRepository = $MySQLRepository;
        $this->config = $config;
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
            $this->eventInfo
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