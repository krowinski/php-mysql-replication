<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');

define('DEBUG', true);
define('ROOT', dirname(__FILE__) .'/');

class BinLogException extends Exception
{
}

require_once 'Connect.php';

function e()
{

//    print_r(func_get_args());
    debug_print_backtrace();
    die();
}

function dpack($pack) {
    $field=bin2hex($pack);
    $field=chunk_split($field,2,"\\x");
    $field= "\\x" . substr($field,0,-2);
    echo $field . PHP_EOL;
}

set_error_handler("e");





$tryCount = 0;
$count    = 0;
while(1) {

    try {
        $flag = false;
        $tryCount++;
        Connect::init("9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-11719");
        while (1) {
            $result = Connect::analysisBinLog();
            //var_dump($result);
        }
    } catch (BinLogException $e) {
        Log::error('try count ' . $tryCount, 'binlog', Config::$LOG['binlog']['error']);
        Log::error(var_export($e, true), 'binlog', Config::$LOG['binlog']['error']);
        sleep(5);
    }
}