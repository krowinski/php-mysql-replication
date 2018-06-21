<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use MySQLReplication\Socket\SocketException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Event
 * @package MySQLReplication\Event
 */
class Event
{
    const MARIADB_DUMMY_QUERY = '# Dum';
    const EOF_HEADER_VALUE = 254;

    /**
     * @var BinLogSocketConnect
     */
    private $binLogSocketConnect;
    /**
     * @var RowEventFactory
     */
    private $rowEventFactory;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * BinLogPack constructor.
     * @param BinLogSocketConnect $binLogSocketConnect
     * @param RowEventFactory $rowEventFactory
     * @param EventDispatcher $eventDispatcher
     * @param CacheInterface $cache
     */
    public function __construct(
        BinLogSocketConnect $binLogSocketConnect,
        RowEventFactory $rowEventFactory,
        EventDispatcher $eventDispatcher,
        CacheInterface $cache
    ) {
        $this->binLogSocketConnect = $binLogSocketConnect;
        $this->rowEventFactory = $rowEventFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
    }

    /**
     * @throws BinaryDataReaderException
     * @throws BinLogException
     * @throws EventException
     * @throws MySQLReplicationException
     * @throws JsonBinaryDecoderException
     * @throws InvalidArgumentException
     * @throws SocketException
     */
    public function consume()
    {
        $binaryDataReader = new BinaryDataReader($this->binLogSocketConnect->getResponse());

        // check EOF_Packet -> https://dev.mysql.com/doc/internals/en/packet-EOF_Packet.html
        if (self::EOF_HEADER_VALUE === $binaryDataReader->readUInt8()) {
            return;
        }

        // decode all events data
        $eventInfo = $this->createEventInfo($binaryDataReader);

        $eventDTO = null;

        // always parse table map event but propagate when needed (we need this for creating table cache)
        if (ConstEventType::TABLE_MAP_EVENT === $eventInfo->getType()) {
            $eventDTO = $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)->makeTableMapDTO();
        }

        if (!Config::checkEvent($eventInfo->getType())) {
            return;
        }

        if (in_array(
            $eventInfo->getType(), [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2], true
        )) {
            $eventDTO = $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)->makeUpdateRowsDTO();
        } else if (in_array(
            $eventInfo->getType(), [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2], true
        )) {
            $eventDTO = $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)->makeWriteRowsDTO();
        } else if (in_array(
            $eventInfo->getType(), [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2], true
        )) {
            $eventDTO = $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)->makeDeleteRowsDTO();
        } else if (ConstEventType::XID_EVENT === $eventInfo->getType()) {
            $eventDTO = (new XidEvent($eventInfo, $binaryDataReader))->makeXidDTO();
        } else if (ConstEventType::ROTATE_EVENT === $eventInfo->getType()) {
            $this->cache->clear();
            $eventDTO = (new RotateEvent($eventInfo, $binaryDataReader))->makeRotateEventDTO();
        } else if (ConstEventType::GTID_LOG_EVENT === $eventInfo->getType()) {
            $eventDTO = (new GtidEvent($eventInfo, $binaryDataReader))->makeGTIDLogDTO();
        } else if (ConstEventType::QUERY_EVENT === $eventInfo->getType()) {
            $eventDTO = $this->filterDummyMariaDbEvents(
                (new QueryEvent($eventInfo, $binaryDataReader))->makeQueryDTO()
            );
        } else if (ConstEventType::MARIA_GTID_EVENT === $eventInfo->getType()) {
            $eventDTO = (new MariaDbGtidEvent($eventInfo, $binaryDataReader))->makeMariaDbGTIDLogDTO();
        } else if (ConstEventType::FORMAT_DESCRIPTION_EVENT === $eventInfo->getType()) {
            $eventDTO = new FormatDescriptionEventDTO($eventInfo);
        } else if (ConstEventType::HEARTBEAT_LOG_EVENT === $eventInfo->getType()) {
            $eventDTO = new HeartbeatDTO($eventInfo);
        }

        $this->dispatch($eventDTO);
    }

    /**
     * @param BinaryDataReader $binaryDataReader
     * @return EventInfo
     */
    private function createEventInfo(BinaryDataReader $binaryDataReader)
    {
        return new EventInfo(
            $binaryDataReader->readInt32(),
            $binaryDataReader->readUInt8(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readUInt16(),
            $this->binLogSocketConnect->getCheckSum(),
            $this->binLogSocketConnect->getBinLogCurrent()
        );
    }

    /**
     * @param QueryDTO $queryDTO
     * @return QueryDTO|null
     */
    private function filterDummyMariaDbEvents(QueryDTO $queryDTO)
    {
        if (BinLogServerInfo::isMariaDb() && false !== strpos($queryDTO->getQuery(), self::MARIADB_DUMMY_QUERY)) {
            return null;
        }

        return $queryDTO;
    }

    /**
     * @param EventDTO $eventDTO
     */
    private function dispatch(EventDTO $eventDTO = null)
    {
        if (null !== $eventDTO) {
            $this->eventDispatcher->dispatch($eventDTO->getType(), $eventDTO);
        }
    }
}
