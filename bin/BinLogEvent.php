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
    public static $TABLE_NAME;

    public static $SCHEMA_NAME;

    public static $TABLE_MAP;
    /**
     * @var BinLogPack
     */
    public static $PACK;
    public static $PACK_SIZE;
    public static $FLAGS;
    public static $EXTRA_DATA_LENGTH;
    public static $EXTRA_DATA;
    public static $SCHEMA_LENGTH;
    public static $COLUMNS_NUM;

    public static $bitCountInByte = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        4, 5, 5, 6, 5, 6, 6, 7, 5, 6, 6, 7, 6, 7, 7, 8,
        ];

    public static function _init(BinLogPack $pack,$event_type, $size = 0) {

        self::$PACK       = $pack;
        self::$EVENT_TYPE = $event_type;
        self::$PACK_SIZE  = $size;
    }

    public static function readTableId()
    {
        $a = (int)(ord(self::$PACK->read(1)) & 0xFF);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 8);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 16);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 24);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 32);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 40);
        return $a;
    }

    public static function bitCount($bitmap) {
        $n = 0;
        for($i=0;$i<strlen($bitmap);$i++) {
            $bit = $bitmap[$i];
            if(is_string($bit)) {
                $bit = ord($bit);
            }
            $n += self::$bitCountInByte[$bit];
        }
        return $n;
    }


}