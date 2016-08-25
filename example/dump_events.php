<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\MySQLReplicationFactory;
use MySQLReplication\Config\ConfigService;

$binLogStream = new MySQLReplicationFactory(
    (new ConfigService())->makeConfigFromArray([
        'user' => 'root',
        'ip' => '192.168.1.6',
        'password' => 'testtest',
        'mariaDbGtid' => '1-1-3,0-1-88',
        //'gtid' => '9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592',
    ])
);
while (1)
{
    $result = $binLogStream->getBinLogEvent();
    if (!is_null($result))
    {
        // all events got __toString() implementation
        echo $result;

        // all events got JsonSerializable implementation
        //echo json_encode($result, JSON_PRETTY_PRINT);

        echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}