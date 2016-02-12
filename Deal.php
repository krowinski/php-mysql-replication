<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 16/1/5
 * Time: 下午2:59
 */
require dirname(__FILE__) . '/../kafkaphp/autoload.php';
class Deal {

    private static $_KAFKA = null;

    private static $_CONFIG = [
        1 => 'localhost:2181'
    ];

    private static $_TOPIC = 'uid_order_center';

    private static function _init() {
        return self::_getKafkaClient();
    }

    private static function _getKafkaClient() {

        if(self::$_KAFKA) {
            return self::$_KAFKA;
        }
        //connect kafka

        try{
            self::$_KAFKA = \Kafka\Produce::getInstance(self::$_CONFIG[1], 3000);
        }catch(\Kafka\Exception $e) {
            Log::error('connect to kafka error', 'connectError', Config::$LOG['kafka']['error']);
            return false;
        }
        return true;
    }


    public static function push($data) {
        if(self::_init() === false) return false;

        $lines = [];
        foreach($data as $value) {
            $lines[] =json_encode($value);
        }

        // push to kafka
        if($lines) {
            try {
                self::$_KAFKA->setRequireAck(-1);

                self::$_KAFKA->setMessages(self::$_TOPIC, 0, $lines);
                $result = self::$_KAFKA->send();
                // 写入成功
                if($result[self::$_TOPIC][0]['errCode']!=0) {
                    Log::error('write to kafka error', 'writeError', Config::$LOG['kafka']['error']);
                    self::$_KAFKA = null;
                    return false;
                }
                if(DEBUG) {
                   Log::notice(json_encode($result), 'kafkaPush', Config::$LOG['kafka']['notice']);
                }
                return true;
            }catch (\Kafka\Exception $e) {
                    /*
                $trace = $e->getTrace();
                foreach($trace as &$value){
                    if($value['class'] == 'Deal' && $value['function'] == 'push') {
                        $value['args'] = null;
                    }
                }*/
                \Kafka\Produce::destoryInstance();
                self::$_KAFKA = null;
                $message = [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                   // 'trace'   => $trace,
                ];
                Log::error('push to kafka error' . var_export($message, true), 'pushError', Config::$LOG['kafka']['error']);
                return false;
            }

        }
        return false;

    }
}
