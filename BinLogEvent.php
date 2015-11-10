<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/7
 * Time: 下午11:54
 */
class BinLogEvent {


    public static $EVENT_TYPE;
    public static $TABLE_ID;
    public static $TABLE_MAP;
    public static $PACK;
    public static $FLAGS;
    public static $EXTRA_DATA_LENGTH;
    public static $EXTRA_DATA;

    public static function _init(BinLogPack $pack,$event_type) {

        self::$PACK = $pack;
        self::$EVENT_TYPE = $event_type;
    }
}