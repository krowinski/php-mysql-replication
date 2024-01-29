<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

error_reporting(E_ALL);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\EventSubscribers;
use MySQLReplication\MySQLReplicationFactory;

/**
 * Simple benchmark to test how fast events are consumed
 */
class benchmark
{
    private const DB_NAME = 'mysqlreplication_test';
    private const DB_USER = 'root';
    private const DB_PASS = 'root';
    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = 3322;

    private MySQLReplicationFactory $binLogStream;

    public function __construct()
    {
        $conn = $this->getConnection();
        $conn->executeStatement('DROP DATABASE IF EXISTS ' . self::DB_NAME);
        $conn->executeStatement('CREATE DATABASE ' . self::DB_NAME);
        $conn->executeStatement('USE ' . self::DB_NAME);
        $conn->executeStatement('CREATE TABLE test (i INT) ENGINE = MEMORY');
        $conn->executeStatement('INSERT INTO test VALUES(1)');
        $conn->executeStatement('CREATE TABLE test2 (i INT) ENGINE = MEMORY');
        $conn->executeStatement('INSERT INTO test2 VALUES(1)');
        $conn->executeStatement('RESET MASTER');

        $this->binLogStream = new MySQLReplicationFactory(
            (new ConfigBuilder())
                ->withUser(self::DB_USER)
                ->withPassword(self::DB_PASS)
                ->withHost(self::DB_HOST)
                ->withPort(self::DB_PORT)
                ->withEventsOnly(
                    [
                        ConstEventType::UPDATE_ROWS_EVENT_V2->value,
                        // for mariadb v1
                        ConstEventType::UPDATE_ROWS_EVENT_V1->value,
                    ]
                )
                ->withSlaveId(9999)
                ->withDatabasesOnly([self::DB_NAME])
                ->build()
        );

        $this->binLogStream->registerSubscriber(
            new class() extends EventSubscribers {
                private float $start;

                private int $counter = 0;

                public function __construct()
                {
                    $this->start = microtime(true);
                }

                public function onUpdate(UpdateRowsDTO $event): void
                {
                    ++$this->counter;
                    if (0 === ($this->counter % 1000)) {
                        echo ((int)($this->counter / (microtime(
                            true
                        ) - $this->start)) . ' event by seconds (' . $this->counter . ' total)') . PHP_EOL;
                    }
                }
            }
        );
    }

    public function run(): void
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new InvalidArgumentException('Could not fork');
        }

        if ($pid) {
            $this->consume();
            pcntl_wait($status);
        } else {
            $this->produce();
        }
    }

    private function getConnection(): Connection
    {
        return DriverManager::getConnection(
            [
                'user' => self::DB_USER,
                'password' => self::DB_PASS,
                'host' => self::DB_HOST,
                'port' => self::DB_PORT,
                'driver' => 'pdo_mysql',
                'dbname' => self::DB_NAME,
            ]
        );
    }

    private function consume(): void
    {
        $this->binLogStream->run();
    }

    private function produce(): void
    {
        $conn = $this->getConnection();

        echo 'Start insert data' . PHP_EOL;

        /** @phpstan-ignore-next-line */
        while (1) {
            $conn->executeStatement('UPDATE  test SET i = i + 1;');
            $conn->executeStatement('UPDATE test2 SET i = i + 1;');
        }
    }
}

(new benchmark())->run();
