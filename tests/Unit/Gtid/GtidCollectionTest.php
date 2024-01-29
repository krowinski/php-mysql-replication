<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Gtid;

use MySQLReplication\Gtid\Gtid;
use MySQLReplication\Gtid\GtidCollection;
use PHPUnit\Framework\TestCase;

class GtidCollectionTest extends TestCase
{
    private GtidCollection $gtidCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gtidCollection = new GtidCollection();

        $this->gtidCollection->add(new Gtid('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592'));
        $this->gtidCollection->add(new Gtid('BBBBBBBB-CCCC-FFFF-DDDD-AAAAAAAAAAAA:1'));
    }

    public function testShouldGetEncodedLength(): void
    {
        self::assertSame(88, $this->gtidCollection->getEncodedLength());
    }

    public function testShouldGetEncoded(): void
    {
        self::assertSame(
            '02000000000000009b1c8d182a7611e5a26b000c2976f3f301000000000000000100000000000000b8b5020000000000bbbbbbbbccccffffddddaaaaaaaaaaaa010000000000000001000000000000000200000000000000',
            bin2hex($this->gtidCollection->getEncoded())
        );
    }

    public function testShouldCreateCollection(): void
    {
        self::assertCount(1, GtidCollection::makeCollectionFromString('9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592'));
    }
}
