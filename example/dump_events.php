<?php

declare(strict_types=1);

error_reporting(E_ALL);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Your db configuration
 * @see ConfigBuilder
 * @link https://github.com/krowinski/php-mysql-replication/blob/master/README.md
 */
$binLogStream = new MySQLReplicationFactory(
    (new ConfigBuilder())
        ->withUser('root')
        ->withHost('0.0.0.0')
        ->withPassword('root')
        ->withPort(3322)
        ->withHeartbeatPeriod(60)
        ->withEventsIgnore([ConstEventType::HEARTBEAT_LOG_EVENT->value])
        ->build(),
    logger: new Logger('replicator', [new StreamHandler(STDOUT)])
);

/**
 * Register your events handler
 * @see EventSubscribers
 */
$binLogStream->registerSubscriber(
    new class() extends EventSubscribers {
        public function allEvents(EventDTO $event): void
        {
            // all events got __toString() implementation
            #echo $event;

            // all events got JsonSerializable implementation
            echo json_encode($event, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

            echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
        }
    }
);

// start consuming events
$binLogStream->run();
