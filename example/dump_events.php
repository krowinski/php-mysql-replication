<?php
declare(strict_types=1);

error_reporting(E_ALL);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Your db configuration
 * @see ConfigBuilder
 * @link https://github.com/krowinski/bcmath-extended/blob/master/README.md
 */
$binLogStream = new MySQLReplicationFactory(
    (new ConfigBuilder())
        ->withUser('root')
        ->withHost('127.0.0.1')
        ->withPassword('root')
        ->withPort(3333)
        ->withSlaveId(100)
        ->withHeartbeatPeriod(2)
        ->build()
);

/**
 * Register your events handler
 * @see EventSubscribers
 */
$binLogStream->registerSubscriber(
    new class() extends EventSubscribers
    {
        public function allEvents(EventDTO $event): void
        {
            // all events got __toString() implementation
            echo $event;

            // all events got JsonSerializable implementation
            //echo json_encode($event, JSON_PRETTY_PRINT);

            echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
        }
    }
);

// start consuming events
$binLogStream->run();
