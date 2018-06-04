<?php
declare(strict_types=1);

namespace example;

error_reporting(E_ALL);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\MySQLReplicationFactory;
use MySQLReplication\Socket\SocketException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class BenchmarkEventSubscribers
 * @package example
 */
class BenchmarkEventSubscribers extends EventSubscribers
{
    /**
     * @var int
     */
    private $start;
    /**
     * @var int
     */
    private $counter = 0;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    /**
     * @param UpdateRowsDTO $event
     */
    public function onUpdate(UpdateRowsDTO $event): void
    {
        ++$this->counter;
        if (0 === ($this->counter % 1000)) {
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
     * @var MySQLReplicationFactory
     */
    private $binLogStream;

    /**
     * Benchmark constructor.
     * @throws DBALException
     * @throws ConfigException
     * @throws BinLogException
     * @throws MySQLReplicationException
     * @throws \MySQLReplication\Gtid\GtidException
     * @throws SocketException
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
            (new ConfigBuilder())
                ->withUser('root')
                ->withPassword('root')
                ->withHost('127.0.0.1')
                ->withPort(3333)
                ->withEventsOnly([ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2])
                ->withSlaveId(9999)
                ->withDatabasesOnly([$this->database])
                ->build()
        );

        // register benchmark subscriber handler
        $this->binLogStream->registerSubscriber(new BenchmarkEventSubscribers());
    }

    /**
     * @return Connection
     * @throws DBALException
     */
    private function getConnection(): Connection
    {
        return DriverManager::getConnection(
            [
                'user' => 'root',
                'password' => 'root',
                'host' => '127.0.0.1',
                'port' => 3333,
                'driver' => 'pdo_mysql',
                'dbname' => $this->database
            ]
        );
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     * @throws BinLogException
     * @throws BinaryDataReaderException
     * @throws MySQLReplicationException
     * @throws JsonBinaryDecoderException
     * @throws SocketException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws DBALException
     */
    public function run(): void
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \InvalidArgumentException('Could not fork');
        }

        if ($pid) {
            $this->consume();
            pcntl_wait($status);
        } else {
            $this->produce();
        }
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     * @throws BinLogException
     * @throws BinaryDataReaderException
     * @throws MySQLReplicationException
     * @throws JsonBinaryDecoderException
     * @throws SocketException
     */
    private function consume(): void
    {
        $this->binLogStream->run();
    }

    /**
     * @throws DBALException
     */
    private function produce()
    {
        $conn = $this->getConnection();

        echo 'Start insert data' . PHP_EOL;
        while (1) {
            $conn->exec('UPDATE  test SET i = i + 1;');
            $conn->exec('UPDATE test2 SET i = i + 1;');
        }
    }
}

(new Benchmark())->run();
