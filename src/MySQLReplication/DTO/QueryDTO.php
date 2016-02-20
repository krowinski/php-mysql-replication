<?php

namespace MySQLReplication\DTO;

/**
 * Class QueryEventDTO
 * @package MySQLReplication\DTO
 */
class QueryDTO extends EventDTO implements \JsonSerializable
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
     * @var
     */
    private $database;

    /**
     * QueryEventDTO constructor.
     * @param $date
     * @param $binLogPos
     * @param $eventSize
     * @param $readBytes
     * @param $database
     * @param $executionTime
     * @param $query
     */
    public function __construct(
        $date,
        $binLogPos,
        $eventSize,
        $readBytes,
        $database,
        $executionTime,
        $query
    ) {
        parent::__construct($date, $binLogPos, $eventSize, $readBytes);

        $this->executionTime = $executionTime;
        $this->query = $query;
        $this->database = $database;
    }

    /**
     * @return mixed
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
    public function __toString()
    {
        return PHP_EOL .
            '=== ' . get_class($this) . ' === ' . PHP_EOL .
            'Date: ' . $this->date . PHP_EOL .
            'Log position: ' . $this->binLogPos . PHP_EOL .
            'Event size: ' . $this->eventSize . PHP_EOL .
            'Read bytes: ' . $this->readBytes . PHP_EOL .
            'Database: ' . $this->database . PHP_EOL .
            'Execution time: ' . $this->executionTime . PHP_EOL .
            'Query: ' . $this->query . PHP_EOL;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

