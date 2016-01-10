<?php
/**
 * 包解析
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/19
 * Time: 下午2:30
 */
class Pack{

    private static $_PACK_KEY = 0;
    private static $_PACK;

    private static $_instance = null;


    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function init($pack) {

        if(!self::$_instance) {
            self::$_instance = new self();
        }

        //
        self::$_PACK       = $pack;
        self::$_PACK_KEY   = 0;

    }

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
    public function advance($length = 1) {
        $this->read($length);
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

    public function readInt24()
    {
        $data = unpack("CCC", $this->read(3));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    public function readInt24Be()
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

    public function readInt40Be()
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


}