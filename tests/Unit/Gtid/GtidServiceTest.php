<?php

namespace MySQLReplication\Tests\Unit\Gtid;

use MySQLReplication\Gtid\GtidFactory;
use MySQLReplication\Tests\Unit\BaseTest;

/**
 * Class GtidServiceTest
 * @package Unit\Gtid
 */
class GtidServiceTest extends BaseTest
{
    /**
     * @test
     */
    public function shouldCreateCollection()
    {
        $this->assertInstanceOf('\MySQLReplication\Gtid\GtidCollection', GtidFactory::makeCollectionFromString('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592'));
    }
}