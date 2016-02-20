<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');
ini_set('memory_limit', '8M');

include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\Service\BinLogStream;
use MySQLReplication\Config\Config;

$binLogStream = new BinLogStream(
    new Config('root', '192.168.1.100', 3306, 'root')
);
while (1)
{
    $result = $binLogStream->analysisBinLog();
    if (!is_null($result))
    {
        // all events got __toString() implementation
        echo $result;

        // all events got JsonSerializable implementation
        //echo json_encode($result, JSON_PRETTY_PRINT);

        //echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}
