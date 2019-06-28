<?php
declare(strict_types=1);

namespace MySQLReplication\Gtid;

use Doctrine\Common\Collections\ArrayCollection;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

class GtidCollection extends ArrayCollection
{
    /**
     * @throws GtidException
     */
    public static function makeCollectionFromString(string $gtids): GtidCollection
    {
        $collection = new self();
        foreach (array_filter(explode(',', $gtids)) as $gtid) {
            $collection->add(new Gtid($gtid));
        }

        return $collection;
    }

    public function getEncodedLength(): int
    {
        $l = 8;
        /** @var Gtid $gtid */
        foreach ($this->toArray() as $gtid) {
            $l += $gtid->getEncodedLength();
        }

        return $l;
    }

    public function getEncoded(): string
    {
        $s = BinaryDataReader::pack64bit($this->count());
        /** @var Gtid $gtid */
        foreach ($this->toArray() as $gtid) {
            $s .= $gtid->getEncoded();
        }

        return $s;
    }
}