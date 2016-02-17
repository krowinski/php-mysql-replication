<?php
namespace MySQLReplication\Service;

use MySQLReplication\BinLog\BinLogPack;
use MySQLReplication\BinLog\Connect;
use MySQLReplication\Config\Config;
use MySQLReplication\DataBase\DBHelper;
use MySQLReplication\Pack\PackAuth;

class BinLogStream
{
    /**
     * @var array
     */
    private $onlyDatabases;
    /**
     * @var array
     */
    private $onlyTables;
    /**
     * @var array
     */
    private $ignoredEvents;
    /**
     * @var DBHelper
     */
    private $dbHelper;
    /**
     * @var Connect
     */
    private $connect;
    /**
     * @var BinLogPack
     */
    private $binLogPack;
    /**
     * @var array
     */
    private $onlyEvents;

    /**
     * @param Config $config
     * @param string $gtID -  Use master_auto_position gtid to set position
     * @param string $logFile - Set replication start log file
     * @param string $logPos - Set replication start log pos (resume_stream should be true)
     * @param string $slave_id - server id of this slave
     * @param array $onlyEvents
     * @param array $ignoredEvents - Array of ignored events
     * @param array $onlyTables - An array with the tables you want to watch
     * @param array $onlyDatabases - An array with the schemas you want to watch
     */
    public function __construct(
        Config $config,
        $gtID = '',
        $logFile = '',
        $logPos = '',
        $slave_id = '',
        array $onlyEvents = [],
        array $ignoredEvents = [],
        array $onlyTables = [],
        array $onlyDatabases = []
    ) {
        $this->dbHelper = new DBHelper($config);
        $this->connect = new Connect($config, $this->dbHelper, $gtID, $logFile, $logPos, $slave_id);
        $this->binLogPack = new BinLogPack($this->dbHelper);

        $this->ignoredEvents = $ignoredEvents;
        $this->onlyTables = $onlyTables;
        $this->onlyDatabases = $onlyDatabases;
        $this->onlyEvents = $onlyEvents;
    }

    /**
     * @return array
     */
    public function analysisBinLog()
    {
        $pack = $this->connect->getPacket();
        PackAuth::success($pack);

        $result = $this->binLogPack->init(
            $pack,
            $this->connect->getCheckSum(),
            $this->onlyEvents,
            $this->ignoredEvents,
            $this->onlyTables,
            $this->onlyDatabases
        );

        if (!empty($result))
        {
            return $result;
        }
        return null;
    }
}