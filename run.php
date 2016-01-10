<?php
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
date_default_timezone_set("PRC");

// 调试
define('DEBUG', true);


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
                var_export($result);
                $data[] = $result;
				$count++;
				echo $count."\n";
                if($count%100 == 0) {
                    while(1) {
                        if(pushToKafka($data) === true) {
                            $count = 0;
							$data  = [];
							$flag  = true;
							echo "push to kafka success\n";
                            break;
                        } else{
                            sleep(5);
                        }
                    }
                }
            }
        }
    } catch (BinLogException $e) {
        Log::error('try count' . $tryCount, 'binlog', Config::$LOG['binlog']['error']);
        Log::error(var_export($e, true), 'binlog', Config::$LOG['binlog']['error']);
        sleep(5);
    }
}



function pushToKafka($data) {
    //return Deal::push($data);
}
//nohup /home/work/php54/bin/php run.php > log_0 2>&1 &
//home/work/php54/bin/php run.php
