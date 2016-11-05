<?php

namespace MySQLReplication\Gtid;

use Doctrine\Common\Collections\ArrayCollection;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * Class GtidCollection
 * @package MySQLReplication\Gtid
 */
class GtidCollection extends ArrayCollection
{
    /**
     * @return int
     */
    public function getEncodedLength()
    {
        $l = 8;
        /** @var Gtid $gtid */
        foreach ($this->toArray() as $gtid)
        {
            $l += $gtid->getEncodedLength();
        }

        return $l;
    }

    /**
     * @return string
     */
    public function getEncoded()
    {
        $s = BinaryDataReader::pack64bit($this->count());
        /** @var Gtid $gtid */
        foreach ($this->toArray() as $gtid)
        {
            $s .= $gtid->getEncoded();
        }

        return $s;
    }
}