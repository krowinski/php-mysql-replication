<?php

namespace Unit\Gtid;

use MySQLReplication\Gtid\GtidService;
use Unit\BaseTest;

/**
 * Class GtidServiceTest
 * @package Unit\Gtid
 */
class GtidServiceTest extends BaseTest
{
    /**
     * @var GtidService
     */
    private $gtidService;

    public function setUp()
    {
        parent::setUp();

        $this->gtidService = new GtidService();
    }

    /**
     * @test
     */
    public function shouldCreateCollection()
    {
        $this->assertInstanceOf('\MySQLReplication\Gtid\GtidCollection', $this->gtidService->makeCollectionFromString('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592'));
    }
}