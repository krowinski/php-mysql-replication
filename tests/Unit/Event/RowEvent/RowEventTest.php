<?php


namespace Unit\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Config\Config;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\RowEvent;
use MySQLReplication\Repository\MySQLRepository;
use Unit\BaseTest;

/**
 * Class RowEventTest
 * @package Unit\Event\RowEvent
 */
class RowEventTest extends BaseTest
{
    /**
     * @var RowEvent
     */
    private $rowEvent;
    /**
     * @var EventInfo|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventInfo;
    /**
     * @var BinaryDataReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $binaryDataReader;
    /**
     * @var MySQLRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mySQLRepository;
    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->mySQLRepository = $this->getMockBuilder(MySQLRepository::class)->disableOriginalConstructor()->getMock();
        $this->binaryDataReader = $this->getMockBuilder(BinaryDataReader::class)->disableOriginalConstructor()->getMock();
        $this->eventInfo = $this->getMockBuilder(EventInfo::class)->disableOriginalConstructor()->getMock();

        $this->rowEvent = new RowEvent(
            $this->config,
            $this->mySQLRepository,
            $this->binaryDataReader,
            $this->eventInfo
        );
    }

    /**
     * @test
     */
    public function shouldMakeUpdateRowsDTO()
    {
        
    }
}