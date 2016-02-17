<?php
include __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);

use MySQLReplication\Config\Config;
use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Service\BinLogStream;



/**
 * Class Base
 */
class Benchmark
{
    /**
     * @var string
     */
    private $database = 'mysqlreplication_test';
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;
    /**
     * @var Config
     */
    private $config;

    public function __construct()
    {
        $this->config = new Config('root', '192.168.1.100', 3306, 'root');
        $this->conn = (new DBHelper($this->config))->getConnection();

        $this->conn->exec("DROP DATABASE IF EXISTS " . $this->database);
        $this->conn->exec("CREATE DATABASE " . $this->database);
        $this->conn->exec("USE " . $this->database);
        $this->conn->exec("CREATE TABLE test (i INT) ENGINE = MEMORY");
        $this->conn->exec("INSERT INTO test VALUES(1)");
        $this->conn->exec("CREATE TABLE test2 (i INT) ENGINE = MEMORY");
        $this->conn->exec("INSERT INTO test2 VALUES(1)");
        $this->conn->exec("RESET MASTER");

        $this->binLogStream =  new BinLogStream(
            $this->config,
            '',
            '',
            '',
            '',
            [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2]
        );
    }

    public function consume()
    {
        $start = microtime(true);
        $i = 0.0;

        while (1)
        {
            $result = $this->binLogStream->analysisBinLog();
            if (!is_null($result))
            {
                $i += 1.0;
                if (0 === ($i % 1000))
                {
                    echo ((int)($i / (microtime(true) - $start)) . ' event by seconds (' . $i . ' total)') . PHP_EOL;
                }
            }
        }
    }

    public function run()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            $this->consume();
            pcntl_wait($status);
        } else {
            $this->produce();
        }

    }

    public function produce()
    {
        $this->conn = (new DBHelper($this->config))->getConnection();
        $this->conn->exec("USE " . $this->database);

        echo 'Start insert data' . PHP_EOL;
        while (1)
        {

            $this->conn->exec("UPDATE test SET i = i + 1;");
            $this->conn->exec("UPDATE test2 SET i = i + 1;");
        }
    }

}


(new Benchmark())->run();