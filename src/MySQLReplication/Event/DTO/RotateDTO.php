<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class RotateDTO
 * @package MySQLReplication\DTO
 */
class RotateDTO extends EventDTO
{
    /**
     * @var int
     */
    private $position;
    /**
     * @var string
     */
    private $next_binlog;
    /**
     * @var string
     */
    private $type = ConstEventsNames::ROTATE;

    /**
     * RotateDTO constructor.
     * @param EventInfo $eventInfo
     * @param $position
     * @param $next_binlog
     */
    public function __construct(
        EventInfo $eventInfo,
        $position,
        $next_binlog
    ) {
        parent::__construct($eventInfo);

        $this->position = $position;
        $this->next_binlog = $next_binlog;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getNextBinlog()
    {
        return $this->next_binlog;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
            'Binlog position: ' . $this->position . PHP_EOL .
            'Binlog filename: ' . $this->next_binlog . PHP_EOL;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}