<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
date_default_timezone_set("PRC");

// 调试
define('DEBUG', true);
define('ROOT', dirname(__FILE__) .'/');

class BinLogException extends Exception
{
    public function __construct($errmsg)
    {
        parent::__construct($errmsg);
    }
}

require_once "Connect.php";
//require_once "Deal.php";

$tryCount = 0;
$count    = 0;

while(1) {

    try {
        $flag = false;
        $tryCount++;
        Connect::init();
        while (1) {
            $result = Connect::analysisBinLog($flag);
            $flag = false;
            if ($result) {
                var_dump($result);
                $data[] = $result;
                $count++;
                Log::out($count);
                if($count%100 == 0) {
                    while(1) {
                        if(pushToKafka($data) === true) {
                            $count = 0;
                            $data  = [];
                            $flag  = true;
                            Log::out("push to kafka success");
                            break;
                        } else{
                            sleep(5);
                        }
                    }
                }
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