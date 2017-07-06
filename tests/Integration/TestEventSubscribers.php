<?php

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;

/**
 * Class TestEventSubscribers
 * @package MySQLReplication\Integration
 */
class TestEventSubscribers extends EventSubscribers
{
    /**
     * @var BaseTest
     */
    private $baseTest;

    /**
     * MyEventSubscribers constructor.
     * @param BaseTest $baseTest
     */
    public function __construct(BaseTest $baseTest)
    {
        $this->baseTest = $baseTest;
    }

    /**
     * @param EventDTO $event
     */
    public function allEvents(EventDTO $event)
    {
        $this->baseTest->setEvent($event);
    }
}