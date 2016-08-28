<?php

namespace MySQLReplication\Gtid;

/**
 * Class GtidService
 * @package MySQLReplication\Gtid
 */
class GtidService
{
    /**
     * GtidSet constructor.
     */
    public function __construct()
    {
        $this->GtidCollection = new GtidCollection();
    }

    /**
     * @param string $gtids
     * @return GtidCollection
     * @throws GtidException
     */
    public function makeCollectionFromString($gtids)
    {
        foreach (array_filter(explode(',', $gtids)) as $gtid)
        {
            $this->GtidCollection->add(new Gtid($gtid));
        }

        return $this->GtidCollection;
    }
}