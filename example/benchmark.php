<?php
include __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Doctrine\DBAL\DriverManager;
use MySQLReplication\BinLogStream;
use MySQLReplication\Config\ConfigService;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\UpdateRowsDTO;

/**
 * Class Benchmark
 */
class Benchmark
{
    /**
     * @var string
     */
    private $database = 'mysqlreplication_test';

    /**
     * Benchmark constructor.
     */
    public function __construct()
    {
        $conn = $this->getConnection();
        $conn->exec("DROP DATABASE IF EXISTS " . $this->database);
        $conn->exec("CREATE DATABASE " . $this->database);
        $conn->exec("USE " . $this->database);
        $conn->exec("CREATE TABLE test (i INT) ENGINE = MEMORY");
        $conn->exec("INSERT INTO test VALUES(1)");
        $conn->exec("CREATE TABLE test2 (i INT) ENGINE = MEMORY");
        $conn->exec("INSERT INTO test2 VALUES(1)");
        $conn->exec("RESET MASTER");

        $this->binLogStream = new BinLogStream(
            (new ConfigService())->makeConfigFromArray([
                'user' => 'root',
                'host' => '192.168.1.100',
                'password' => 'root',
                'eventsOnly' => [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2],
                'slaveId' => 9999
            ])
        );
    }

    /**
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getConnection()
    {
        return DriverManager::getConnection([
            'user' => 'root',
            'password' => 'root',
            'host' => '192.168.1.100',
            'port' => 3306,
            'driver' => 'pdo_mysql',
            'dbname' => $this->database
        ]);
    }

    /**
     *
     */
    public function run()
    {
        $pid = pcntl_fork();
        if ($pid == -1)
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
     *
     */
    private function consume()
    {
        $start = microtime(true);
        $i = 0;

        while (1)
        {
            $result = $this->binLogStream->getBinLogEvent();
            if ($result instanceof UpdateRowsDTO)
            {
                $i += 1;
                if (0 === ($i % 1000))
                {
                    echo ((int)($i / (microtime(true) - $start)) . ' event by seconds (' . $i . ' total)') . PHP_EOL;
                }
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function produce()
    {
        $conn = $this->getConnection();

        echo 'Start insert data' . PHP_EOL;
        while (1)
        {
            $conn->exec("UPDATE test SET i = i + 1;");
            $conn->exec("UPDATE test2 SET i = i + 1;");
        }

        $conn->close();
    }
}

(new Benchmark())->run();