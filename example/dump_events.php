<?php

namespace example;

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\Config\ConfigService;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

$binLogStream = new MySQLReplicationFactory(
    (new ConfigService())->makeConfigFromArray([
        'user' => 'root',
        'ip' => '127.0.0.1',
        'password' => 'root',
        //'gtid' => 'b29263f9-f378-11e5-8129-000c2976f3f3:1-4854',
    ])
);

/**
 * Class BenchmarkEventSubscribers
 * @package example
 */
class MyEventSubscribers extends EventSubscribers
{
    /**
     * @param EventDTO $event (your own handler more in EventSubscribers class )
     */
    public function allEvents(EventDTO $event)
    {
        // all events got __toString() implementation
        echo $event;

        // all events got JsonSerializable implementation
        //echo json_encode($result, JSON_PRETTY_PRINT);

        echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}

// register your events handler here
$binLogStream->registerSubscriber(new MyEventSubscribers());

// start consuming events
while (1) {
    $binLogStream->binLogEvent();
}