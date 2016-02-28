<?php

namespace Unit\Gtid;

use MySQLReplication\Gtid\Gtid;
use MySQLReplication\Gtid\GtidCollection;
use Unit\BaseTest;

/**
 * Class GtidCollectionTest
 * @package Unit\Gtid
 */
class GtidCollectionTest extends BaseTest
{
    /**
     * @var GtidCollection
     */
    private $gtidCollection;

    public function setUp()
    {
        parent::setUp();

        $this->gtidCollection = new GtidCollection();

        $this->gtidCollection->add(new Gtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592'));
        $this->gtidCollection->add(new Gtid('BBBBBBBB-CCCC-FFFF-DDDD-AAAAAAAAAAAA:1'));
    }

    /**
     * @test
     */
    public function shouldGetEncodedLength()
    {
        $this->assertSame(88, $this->gtidCollection->getEncodedLength());
    }

    /**
     * @test
     */
    public function shouldGetEncoded()
    {
        $this->assertSame('02000000000000009b1c8d182a7611e5a26b000c2976f3f301000000000000000100000000000000b8b5020000000000bbbbbbbbccccffffddddaaaaaaaaaaaa010000000000000001000000000000000200000000000000', bin2hex($this->gtidCollection->getEncoded()));
    }
}