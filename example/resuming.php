<?php
declare(strict_types=1);

namespace example;

error_reporting(E_ALL);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Your db configuration @see ConfigBuilder for more options
 */
$binLogStream = new MySQLReplicationFactory(
    BinLogBootstrap::startFromPosition(new ConfigBuilder())
        ->withUser('root')
        ->withHost('127.0.0.1')
        ->withPort(3333)
        ->withPassword('root')
        ->build()
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
    public function allEvents(EventDTO $event): void
    {
        // all events got __toString() implementation
        echo $event;

        // all events got JsonSerializable implementation
        //echo json_encode($event, JSON_PRETTY_PRINT);

        echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;

        // save event for resuming it later
        BinLogBootstrap::save($event->getEventInfo()->getBinLogCurrent());
    }
}

/**
 * Class SaveBinLogPos
 * @package example
 */
class BinLogBootstrap
{
    /**
     * @var string
     */
    private static $fileAndPath;

    /**
     * @return string
     */
    private static function getFileAndPath(): string
    {
        if (null === self::$fileAndPath) {
            self::$fileAndPath = sys_get_temp_dir() . '/bin-log-replicator-last-position';
        }
        return self::$fileAndPath;
    }

    /**
     * @param BinLogCurrent $binLogCurrent
     */
    public static function save(BinLogCurrent $binLogCurrent): void
    {

        echo 'saving file:' . $binLogCurrent->getBinFileName() . ', position:' . $binLogCurrent->getBinLogPosition() . ' bin log position' . PHP_EOL;

        // can be redis/nosql/file - something fast!
        // to speed up you can save every xxx time
        // you can also use signal handler for ctrl + c exiting script to wait for last event
        file_put_contents(self::getFileAndPath(), serialize($binLogCurrent));
    }

    /**
     * @param ConfigBuilder $builder
     * @return ConfigBuilder
     */
    public static function startFromPosition(ConfigBuilder $builder): ConfigBuilder
    {
        if (!is_file(self::getFileAndPath())) {
            return $builder;
        }

        /** @var BinLogCurrent $binLogCurrent */
        $binLogCurrent = unserialize(file_get_contents(self::getFileAndPath()));

        echo 'starting from file:' . $binLogCurrent->getBinFileName() . ', position:' . $binLogCurrent->getBinLogPosition() . ' bin log position' . PHP_EOL;

        return $builder
            ->withBinLogFileName($binLogCurrent->getBinFileName())
            ->withBinLogPosition($binLogCurrent->getBinLogPosition());
    }
}

// register your events handler here
$binLogStream->registerSubscriber(new MyEventSubscribers());

// start consuming events
$binLogStream->run();

