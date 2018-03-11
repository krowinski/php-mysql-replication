<?php

namespace BinaryDataReader\Unit;

use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\ConfigFactory;
use MySQLReplication\Repository\RepositoryInterface;
use MySQLReplication\Socket\SocketInterface;
use MySQLReplication\Tests\Unit\BaseTest;


class BinLogSocketConnectTest extends BaseTest
{
    /**
     * @var RepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;
    /**
     * @var SocketInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $socket;
    /**
     * @var BinLogSocketConnect
     */
    private $binLogSocketConnect;

    /**
     * @var string
     */
    private $example
        = '
5.6.38-83.0-log' . "\0" . 'P' . "\0" . '' . "\0" . '-"_:E-N$' . "\0" . '▒' . "\0" . '▒' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . 'dM4ZI@F|`L;#' . "\0" . 'mysql_native_password' . "\0" . '';


    public function setUp()
    {
        parent::setUp();


        $this->repository = $this->getMockBuilder(RepositoryInterface::class)->disableOriginalConstructor()->getMock();
        $this->socket = $this->getMockBuilder(SocketInterface::class)->disableOriginalConstructor()->getMock();
        $this->socket->method('readFromSocket')->willReturnCallback(function ($length) {
            $return = substr($this->example, 0, $length);
            $this->example = substr($this->example, $length);

            return $return;
        });

        $this->binLogSocketConnect = new BinLogSocketConnect(
            $this->repository,
            $this->socket
        );
    }

    /**
     * @test
     */
    public function shouldGetResponse()
    {
        self::assertSame('', $this->binLogSocketConnect->getResponse());
    }
}