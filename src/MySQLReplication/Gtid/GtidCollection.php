<?php

namespace MySQLReplication\Gtid;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class GtidSet
 */
class GtidCollection extends ArrayCollection
{
    /**
     * @return int
     */
    public function getEncodedPacketLength()
    {
        $l = 8;
        /** @var GtidEntity $gtid */
        foreach ($this->toArray() as $gtid)
        {
            $l += $gtid->encoded_length();
        }

        return $l;
    }

    /**
     * @return string
     */
    public function getEncodedPacket()
    {
        $s = pack('Q', $this->count());
        /** @var GtidEntity $gtid */
        foreach ($this->toArray() as $gtid)
        {
            $s .= $gtid->encode();
        }

        return $s;
    }
}