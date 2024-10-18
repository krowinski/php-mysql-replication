<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;

class TestEventSubscribers extends EventSubscribers
{
    private $baseTest;

    public function __construct(BaseTest $baseTest)
    {
        $this->baseTest = $baseTest;
    }

    public function allEvents(EventDTO $event): void
    {
        $this->baseTest->setEvent($event);
    }
}