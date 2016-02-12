<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
date_default_timezone_set("PRC");

// 调试
define('DEBUG', true);
define('ROOT', dirname(__FILE__) .'/');

class BinLogException extends Exception
{
}

require_once "Connect.php";
//require_once "Deal.php";

$tryCount = 0;
$count    = 0;

while(1) {

    try {
        $flag = false;
        $tryCount++;
        Connect::init('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-1242');
        while (1) {
            $result = Connect::analysisBinLog($flag);
            $flag = false;
            if ($result) {
                var_dump($result);
                $data[] = $result;
                $count++;

            }
        }
    } catch (BinLogException $e) {
        Log::error('try count ' . $tryCount, 'binlog', Config::$LOG['binlog']['error']);
        Log::error(var_export($e, true), 'binlog', Config::$LOG['binlog']['error']);
        sleep(5);
    }
}



function pushToKafka($data) {
    return true;
    //return Deal::push($data);
}