<?php
namespace MySQLReplication;

use Doctrine\DBAL\DriverManager;
use MySQLReplication\Event\Event;
use MySQLReplication\BinLog\BinLogConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Repository\MySQLRepository;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\GTIDLogDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Gtid\GtidCollection;
use MySQLReplication\Gtid\GtidService;
use MySQLReplication\BinLog\BinLogAuth;
use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use MySQLReplication\Event\RowEvent\RowEventService;

/**
 * Class MySQLReplicationFactory
 * @package MySQLReplication
 */
class MySQLReplicationFactory
{
    /**
     * @var MySQLRepository
     */
    private $MySQLRepository;
    /**
     * @var BinLogConnect
     */
    private $binLogConnect;
    /**
     * @var Event
     */
    private $event;
    /**
     * @var BinLogAuth
     */
    private $binLogAuth;
    /**
     * @var GtidCollection
     */
    private $GtidCollection;
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;
    /**
     * @var BinaryDataReaderService
     */
    private $binaryDataReaderService;

    /**
     * @param Config $config
     * @throws \MySQLReplication\Exception\MySQLReplicationException
     */
    public function __construct(Config $config)
    {
        $this->connection = DriverManager::getConnection([
            'user' => $config->getUser(),
            'password' => $config->getPassword(),
            'host' => $config->getIp(),
            'port' => $config->getPort(),
            'driver' => 'pdo_mysql',
        ]);
        $this->binLogAuth = new BinLogAuth();
        $this->MySQLRepository = new MySQLRepository($this->connection);
        $this->GtidCollection = (new GtidService())->makeCollectionFromString($config->getGtid());
        $this->binLogConnect = new BinLogConnect($config, $this->MySQLRepository, $this->binLogAuth, $this->GtidCollection);
        $this->binLogConnect->connectToStream();
        $this->binaryDataReaderService = new BinaryDataReaderService();
        $this->rowEventService = new RowEventService($config, $this->MySQLRepository);
        $this->event = new Event($config, $this->binLogConnect, $this->MySQLRepository, $this->binaryDataReaderService, $this->rowEventService);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getDbConnection()
    {
        return $this->connection;
    }

    /**
     * @return DeleteRowsDTO|EventDTO|GTIDLogDTO|QueryDTO|\MySQLReplication\Event\DTO\RotateDTO|TableMapDTO|UpdateRowsDTO|WriteRowsDTO|\MySQLReplication\Event\DTO\XidDTO
     * @throws \MySQLReplication\Exception\MySQLReplicationException
     */
    public function getBinLogEvent()
    {
        return $this->event->consume();
    }
}