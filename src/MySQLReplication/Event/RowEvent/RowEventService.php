<?php

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Config\Config;
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
     */
    public function __construct(Config $config, MySQLRepository $mySQLRepository)
    {
        $this->rowEventBuilder = new RowEventBuilder($config, $mySQLRepository);
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