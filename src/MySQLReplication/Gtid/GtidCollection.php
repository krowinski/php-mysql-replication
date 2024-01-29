<?php

declare(strict_types=1);

namespace MySQLReplication\Gtid;

use Doctrine\Common\Collections\ArrayCollection;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * @extends ArrayCollection<int, Gtid>
 */
class GtidCollection extends ArrayCollection
{
    public static function makeCollectionFromString(string $gtids): self
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
        foreach ($this->toArray() as $gtid) {
            $l += $gtid->getEncodedLength();
        }

        return $l;
    }

    public function getEncoded(): string
    {
        $s = BinaryDataReader::pack64bit($this->count());
        foreach ($this->toArray() as $gtid) {
            $s .= $gtid->getEncoded();
        }

        return $s;
    }
}
