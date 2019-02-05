<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class MariaGTIDLogDTO
 * @package MySQLReplication\Event\DTO
 */
class MariaDbGtidLogDTO extends EventDTO
{
    /**
     * @var string
     */
    private $type = ConstEventsNames::MARIADB_GTID;
    /**
     * @var int
     */
    private $flag;
    /**
     * @var int
     */
    private $domainId;
    /**
     * @var int
     */
    private $sequenceNumber;

    public function __construct(
        EventInfo $eventInfo,
        $flag,
        $domainId,
        $sequenceNumber
    ) {
        parent::__construct($eventInfo);

        $this->flag = $flag;
        $this->domainId = $domainId;
        $this->sequenceNumber = $sequenceNumber;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Flag: ' . var_export($this->flag, true) . PHP_EOL .
            'Domain Id: ' . $this->domainId . PHP_EOL .
            'Sequence Number: ' . $this->sequenceNumber . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @return int
     */
    public function getDomainId()
    {
        return $this->domainId;
    }
}