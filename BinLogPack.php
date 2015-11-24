<?php
/**
 * 解析binlog
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/7
 * Time: 下午8:53
 * 读取pack包
 */
class BinLogPack {


    public static $EVENT_INFO;
    public static $EVENT_TYPE;

    private static $_PACK_KEY = 0;
    private static $_PACK;

    private static $_instance = null;


    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function init($pack, $checkSum = true) {

        if(!self::$_instance) {
            self::$_instance = new self();
        }

        //
        self::$_PACK       = $pack;
        self::$_PACK_KEY   = 0;
        self::$EVENT_INFO  = [];



        self::advance(1);

        self::$EVENT_INFO['time'] = $timestamp  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['type'] = self::$EVENT_TYPE = unpack('C', $this->read(1))[1];
        self::$EVENT_INFO['id']   = $server_id  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['size'] = $event_size = unpack('L', $this->read(4))[1];
        //position of the next event
        self::$EVENT_INFO['pos']  = $log_pos    = unpack('L', $this->read(4))[1];//
        self::$EVENT_INFO['flag'] = $flags      = unpack('S', $this->read(2))[1];

        $event_size_without_header = $checkSum === true ? ($event_size -23) : $event_size - 19;

        echo 'next pos -> '.$log_pos;
        echo ' --  typeEvent -> '.self::$EVENT_TYPE."\n";


        if (self::$EVENT_TYPE == ConstEventType::TABLE_MAP_EVENT) {
            RowEvent::tableMap(self::getInstance(), self::$EVENT_TYPE);
            return ;
        } elseif(in_array(self::$EVENT_TYPE,[ConstEventType::UPDATE_ROWS_EVENT_V2,ConstEventType::UPDATE_ROWS_EVENT_V1])) {
            return RowEvent::updateRow(self::getInstance(), self::$EVENT_TYPE);
        }elseif(in_array(self::$EVENT_TYPE,[ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2])) {
            return RowEvent::addRow(self::getInstance(), self::$EVENT_TYPE);
        }elseif(in_array(self::$EVENT_TYPE,[ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2])) {
            return RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE);
        }elseif(self::$EVENT_TYPE == 16) {
            return;
            //var_dump(bin2hex($pack),$this->readUint64());
            //return RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE);
        }elseif(self::$EVENT_TYPE == 4) {

            echo 'pos -> '.$this->readUint64()."\n";
            echo 'filename -> '.$this->read($event_size_without_header-8)."\n";

        }elseif(self::$EVENT_TYPE == ConstEventType::GTID_LOG_EVENT) {
            return;

        }


    }

//    private function

    public function read($length) {
        $length = (int)$length;
        $n='';
        for($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++) {
            $n .= self::$_PACK[$i];
        }

        self::$_PACK_KEY += $length;

        return $n;

    }

    /**
     * @brief 前进步长
     * @param $length
     */
    public  function advance($length) {
        $this->read($length);
    }

    /**
     * @brief read a 'Length Coded Binary' number from the data buffer.
     ** Length coded numbers can be anywhere from 1 to 9 bytes depending
     ** on the value of the first byte.
     ** From PyMYSQL source code
     * @return int|string
     */

    public function readCodedBinary(){
        $c = ord($this->read(1));
        if($c == ConstMy::NULL_COLUMN) {
            return '';
        }
        if($c < ConstMy::UNSIGNED_CHAR_COLUMN) {
            return $c;
        } elseif($c == ConstMy::UNSIGNED_SHORT_COLUMN) {
            return $this->unpackUint16($this->read(ConstMy::UNSIGNED_SHORT_LENGTH));

        }elseif($c == ConstMy::UNSIGNED_INT24_COLUMN) {
            return $this->unpackInt24($this->read(ConstMy::UNSIGNED_INT24_LENGTH));
        }
        elseif($c == ConstMy::UNSIGNED_INT64_COLUMN) {
            return $this->unpackInt64($this->read(ConstMy::UNSIGNED_INT64_LENGTH));
        }
    }

    public function unpackUint16($data) {
        return unpack("S",$data[0] . $data[1])[1];
    }

    public function unpackInt24($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        return $a;
    }

    public function unpackInt64($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        $a += (int)((ord($data[3]) & 0xFF) << 24);
        $a += (int)((ord($data[4]) & 0xFF) << 32);
        $a += (int)((ord($data[5]) & 0xFF) << 40);
        $a += (int)((ord($data[6]) & 0xFF) << 48);
        $a += (int)((ord($data[7]) & 0xFF) << 56);
        return $a;
    }

    public function read_int24()
    {
        $data = unpack("CCC", $this->read(3));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    public function read_int24_be()
    {
        $data = unpack('CCC', $this->read(3));
        $res = ($data[1] << 16) | ($data[2] << 8) | $data[3];
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    //
    public function readUint8()
    {
        return unpack('C', $this->read(1))[1];
    }

    //
    public function readUint16()
    {
        return unpack('S', $this->read(2))[1];
    }

    public function readUint24()
    {
        $data = unpack("CCC", $this->read(3));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    //
    public function readUint32()
    {
        return unpack('I', $this->read(4))[1];
    }

    public function readUint40()
    {
        $data = unpack("CI", $this->read(5));
        return $data[1] + ($data[2] << 8);
    }

    public function read_int40_be()
    {
        $data = unpack("IC", $this->read(5));
        return $data[2] + ($data[1] << 8);
    }

    //
    public function readUint48()
    {
        $data = unpack("vvv", $this->read(6));
        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    //
    public function readUint56()
    {
        $data = unpack("CSI", $this->read(7));
        return $data[1] + ($data[2] << 8) + ($data[3] << 24);
    }

    /*
     * 不支持unsigned long long，溢出
     */
    public function readUint64()
    {
        $d = $this->read(8);
        $unpackArr = unpack('I2', $d);
        //$data = unpack("C*", $d);
        //$r = $data[1] + ($data[2] << 8) + ($data[3] << 16) + ($data[4] << 24);//+
        //$r2= ($data[5]) + ($data[6] << 8) + ($data[7] << 16) + ($data[8] << 24);

        return $unpackArr[1] + ($unpackArr[2] << 32);
    }

    public function readInt64()
    {
        return $this->readUint64();
    }
    
    public function read_uint_by_size($size)
    {

        if($size == 1)
            return $this->readUint8();
        elseif($size == 2)
            return $this->readUint16();
        elseif($size == 3)
            return $this->readUint24();
        elseif($size == 4)
            return $this->readUint32();
        elseif($size == 5)
            return $this->readUint40();
        elseif($size == 6)
            return $this->readUint48();
        elseif($size == 7)
            return $this->readUint56();
        elseif($size == 8)
            return $this->readUint64();
    }
    public function read_length_coded_pascal_string($size)
    {
        $length = $this->read_uint_by_size($size);
        return $this->read($length);
    }

}