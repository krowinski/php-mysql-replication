<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');

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

// $gtid = '', $slave_id = '', $logFile = '', $logPos = '', array $ignoredEvents = [], array $onlyTables = [], $onlyDatabases = []
$binLogStream = new BinLogStream(
    "9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-11719",
    '',
    '',
    '',
    [ ConstEventType::XID_EVENT, ConstEventType::QUERY_EVENT, ConstEventType::GTID_LOG_EVENT, ConstEventType::ROTATE_EVENT ]);
//$binLogStream = new BinLogStream('', 'mysql-bin.000028', '4');
while (1)
{
    $result = $binLogStream->analysisBinLog();
    if (!is_null($result))
    {
        var_dump($result);
    }
}
