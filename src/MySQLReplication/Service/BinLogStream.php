<?php
namespace MySQLReplication\Service;

use MySQLReplication\BinLog\BinLogPack;
use MySQLReplication\BinLog\Connect;
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
     * @param string $gtid -  Use master_auto_position gtid to set position
     * @param string $logFile - Set replication start log file
     * @param string $logPos - Set replication start log pos (resume_stream should be true)
     * @param string $slave_id - server id of this slave
     * @param array $ignoredEvents - Array of ignored events
     * @param array $onlyTables - An array with the tables you want to watch
     * @param array $onlyDatabases - An array with the schemas you want to watch
     */
    public function __construct(
        $gtid = '',
        $logFile = '',
        $logPos = '',
        $slave_id = '',
        array $ignoredEvents = [],
        array $onlyTables = [],
        array $onlyDatabases = []
    ) {
        Connect::init(
            $gtid,
            $logFile,
            $logPos,
            $slave_id
        );
        $this->BinLogPack = new BinLogPack();

        $this->ignoredEvents = $ignoredEvents;
        $this->onlyTables = $onlyTables;
        $this->onlyDatabases = $onlyDatabases;
    }

    /**
     * @return array
     */
    public function analysisBinLog()
    {
        $pack = Connect::_readPacket();
        PackAuth::success($pack);

        $result = $this->BinLogPack->init(
            $pack,
            Connect::getCheckSum(),
            $this->ignoredEvents,
            $this->onlyTables,
            $this->onlyDatabases
        );

        if ($result)
        {
            return $result;
        }
        return null;
    }
}