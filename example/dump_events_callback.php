<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');
include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\GTIDLogDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use MySQLReplication\MySQLReplicationFactory;
use MySQLReplication\Config\ConfigService;

$binLogStream = new MySQLReplicationFactory(
    (new ConfigService())->makeConfigFromArray([
        'user' => 'root',
        'ip' => '127.0.0.1',
        'password' => 'root'
    ])
);

$binLogStream->parseBinLogUsingCallback(function($event)
{
    /** @var DeleteRowsDTO|EventDTO|GTIDLogDTO|QueryDTO|RotateDTO|TableMapDTO|UpdateRowsDTO|WriteRowsDTO|XidDTO $event */
    echo $event;
});