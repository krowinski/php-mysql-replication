<?php

namespace example;

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinLog\Exception\BinLogException;
use MySQLReplication\Config\Exception\ConfigException;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\MySQLReplicationFactory;
use MySQLReplication\Config\ConfigService;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\UpdateRowsDTO;

/**
 * Class BenchmarkEventSubscribers
 * @package example
 */
class BenchmarkEventSubscribers extends EventSubscribers
{
    /**
     * @var int
     */
    private $start = 0;
    /**
     * @var int
     */
    private $counter = 0;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    public function onUpdate(UpdateRowsDTO $event)
    {
        ++$this->counter;
        if (0 === ($this->counter % 1000))
        {
            echo ((int)($this->counter / (microtime(true) - $this->start)) . ' event by seconds (' . $this->counter . ' total)') . PHP_EOL;
        }
    }
}

/**
 * Class Benchmark
 * @package example
 */
class Benchmark
{
    /**
     * @var string
     */
    private $database = 'mysqlreplication_test';

    /**
     * Benchmark constructor.
     * @throws DBALException
     * @throws ConfigException
     * @throws BinLogException
     * @throws MySQLReplicationException
     */
    public function __construct()
    {
        $conn = $this->getConnection();
        $conn->exec('DROP DATABASE IF EXISTS ' . $this->database);
        $conn->exec('CREATE DATABASE ' . $this->database);
        $conn->exec('USE ' . $this->database);
        $conn->exec('CREATE TABLE test (i INT) ENGINE = MEMORY');
        $conn->exec('INSERT INTO test VALUES(1)');
        $conn->exec('CREATE TABLE test2 (i INT) ENGINE = MEMORY');
        $conn->exec('INSERT INTO test2 VALUES(1)');
        $conn->exec('RESET MASTER');

        $this->binLogStream = new MySQLReplicationFactory(
            (new ConfigService())->makeConfigFromArray([
                'user' => 'root',
                'ip' => '127.0.0.1',
                'password' => 'root',
                // we only interest in update row event
                'eventsOnly' => [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2],
                'slaveId' => 9999
            ])
        );

        // register benchmark subscriber handler
        $this->binLogStream->registerSubscriber(new BenchmarkEventSubscribers());
    }

    /**
     * @return Connection
     * @throws DBALException
     */
    private function getConnection()
    {
        return DriverManager::getConnection([
            'user' => 'root',
            'password' => 'root',
            'host' => '127.0.0.1',
            'port' => 3306,
            'driver' => 'pdo_mysql',
            'dbname' => $this->database
        ]);
    }

    /**
     * @throws MySQLReplicationException
     * @throws DBALException
     */
    public function run()
    {
        $pid = pcntl_fork();
        if ($pid === -1)
        {
            die('could not fork');
        }
        else if ($pid)
        {
            $this->consume();
            pcntl_wait($status);
        }
        else
        {
            $this->produce();
        }
    }

    /**
     * @throws MySQLReplicationException
     */
    private function consume()
    {
        while(1) {
            $this->binLogStream->binLogEvent();
        }
    }

    /**
     * @throws DBALException
     */
    private function produce()
    {
        $conn = $this->getConnection();

        echo 'Start insert data' . PHP_EOL;
        while (1)
        {
            $conn->exec('UPDATE test SET i = i + 1;');
            $conn->exec('UPDATE test2 SET i = i + 1;');
        }

        $conn->close();
        die;
    }
}

(new Benchmark())->run();
