<?php

namespace MySQLReplication\Pack;

/**
 * Class GtidSet
 */
class GtidSet
{
    /**
     * @var Gtid []
     */
    private $gtids;

    /**
     * GtidSet constructor.
     * @param $gtids
     */
    public function __construct($gtids)
    {
        foreach (explode(',', $gtids) as $gtid)
        {
            $this->gtids[] = new Gtid($gtid);
        }
    }

    /**
     * @return int
     */
    public function encoded_length()
    {
        $l = 8;

        foreach ($this->gtids as $gtid)
        {
            $l += $gtid->encoded_length();
        }

        return $l;
    }

    /**
     * @return string
     */
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