<?php

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class QueryDTO
 * @package MySQLReplication\Event\DTO
 */
class QueryDTO extends EventDTO
{
    /**
     * @var int
     */
    private $executionTime;
    /**
     * @var string
     */
    private $query;
    /**
     * @var string
     */
    private $database;
    /**
     * @var string
     */
    private $type = ConstEventsNames::QUERY;

    /**
     * QueryEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param string $database
     * @param int $executionTime
     * @param string $query
     */
    public function __construct(
        EventInfo $eventInfo,
        $database,
        $executionTime,
        $query
    ) {
        parent::__construct($eventInfo);

        $this->executionTime = $executionTime;
        $this->query = $query;
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
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
            'Database: ' . $this->database . PHP_EOL .
            'Execution time: ' . $this->executionTime . PHP_EOL .
            'Query: ' . $this->query . PHP_EOL;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

