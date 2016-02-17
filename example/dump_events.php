<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');

ini_set('memory_limit', '8M');

include __DIR__ . '/../vendor/autoload.php';

function dpack($pack)
{
    $field = bin2hex($pack);
    $field = chunk_split($field, 2, "\\x");
    $field = "\\x" . substr($field, 0, -2);
    echo $field . PHP_EOL;
}

use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Service\BinLogStream;
use MySQLReplication\Config\Config;

// $gtid = '', $slave_id = '', $logFile = '', $logPos = '', array $ignoredEvents = [], array $onlyTables = [], $onlyDatabases = []
$binLogStream = new BinLogStream(
    new Config('root', '192.168.1.100', 3306, 'root'),
    "9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-13476",
    '',
    '',
    '',
    [] //[ ConstEventType::XID_EVENT, ConstEventType::ROTATE_EVENT, ConstEventType::GTID_LOG_EVENT, ConstEventType::QUERY_EVENT ]
);
while (1)
{
    $result = $binLogStream->analysisBinLog();
    if (!is_null($result))
    {
        print_r($result);
        echo 'Memory usage' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}
