<?php

namespace MySQLReplication\DTO;

/**
 * Class QueryEventDTO
 * @package MySQLReplication\DTO
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
}

