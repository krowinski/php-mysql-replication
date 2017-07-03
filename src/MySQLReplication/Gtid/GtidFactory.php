<?php

namespace MySQLReplication\Gtid;

/**
 * Class GtidService
 * @package MySQLReplication\Gtid
 */
class GtidFactory
{
    /**
     * @param string $gtids
     * @return GtidCollection
     * @throws GtidException
     */
    public static function makeCollectionFromString($gtids)
    {
        $collection = new GtidCollection();
        foreach (array_filter(explode(',', $gtids)) as $gtid) {
            $collection->add(new Gtid($gtid));
        }

        return $collection;
    }
}