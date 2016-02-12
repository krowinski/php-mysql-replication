<?php

/**
 * Created by PhpStorm.
 * User: fazi
 * Date: 11.02.2016
 * Time: 20:48
 */
class Gtid
{
    private $gtid = '';
    private $intervals = [];
    private $sid = '';

    public function __construct($gtid)
    {
        $this->gtid = $gtid;

        preg_match('/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $this->gtid, $matches);

        $this->sid = $matches[1];
        foreach (array_filter(explode(':', $matches[2])) as $k)
        {
            $this->intervals[] = explode('-', $k);
        }
        $this->sid = str_replace('-', '', $this->sid);


    }

    public function encode()
    {
        // binascii.unhexlify ==  pack('H*', $sid);
        $buffer = pack('H*', $this->sid);
        $buffer .= pack('Q', count($this->intervals));

        foreach ($this->intervals as $interval)
        {
            if (count($interval) != 1)
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[1]);
            }
            else
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[0] + 1);
            }
        }

        //var_dump($this->sid,$interval);

        return $buffer;
    }

    public function encoded_length()
    {
        return (16 + 8 + 2 * 8 * count($this->intervals));
    }
}


class GtidSet
{
    /**
     * @var Gtid []
     */
    private $gtids;

    public function __construct($gtids)
    {
        foreach (explode(',', $gtids) as $gtid)
        {
            $this->gtids[] = new Gtid($gtid);
        }
    }

    public function encoded_length()
    {
        $l = 8;

        foreach ($this->gtids as $gtid)
        {
            $l += $gtid->encoded_length();
        }

        var_dump($l);

        return $l;
    }

    public function encoded()
    {
        $s = pack('Q', count($this->gtids));

        foreach ($this->gtids as $gtid)
        {
            $s .= $gtid->encode();
        }

        return $s;
    }
}

