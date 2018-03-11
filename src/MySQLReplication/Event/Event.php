<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;
use MySQLReplication\BinaryDataReader\BinaryDataReaderFactory;
use MySQLReplication\BinLog\BinLogException;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigException;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use MySQLReplication\Exception\MySQLReplicationException;
use MySQLReplication\JsonBinaryDecoder\JsonBinaryDecoderException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Event
 * @package MySQLReplication\Event
 */
class Event
{
    /**
     * @var BinLogSocketConnect
     */
    private $socketConnect;
    /**
     * @var BinaryDataReaderFactory
     */
    private $packageService;
    /**
     * @var RowEventFactory
     */
    private $rowEventService;
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
     * @param BinLogSocketConnect $socketConnect
     * @param BinaryDataReaderFactory $packageService
     * @param RowEventFactory $rowEventService
     * @param EventDispatcher $eventDispatcher
     * @param CacheInterface $cache
     */
    public function __construct(
        BinLogSocketConnect $socketConnect,
        BinaryDataReaderFactory $packageService,
        RowEventFactory $rowEventService,
        EventDispatcher $eventDispatcher,
        CacheInterface $cache
    ) {
        $this->socketConnect = $socketConnect;
        $this->packageService = $packageService;
        $this->rowEventService = $rowEventService;
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
    }

    /**
     * @throws BinaryDataReaderException
     * @throws BinLogException
     * @throws ConfigException
     * @throws EventException
     * @throws MySQLReplicationException
     * @throws JsonBinaryDecoderException
     * @throws InvalidArgumentException
     * @throws \MySQLReplication\Socket\SocketException
     */
    public function consume()
    {
        $binaryDataReader = $this->packageService->makePackageFromBinaryData($this->socketConnect->getResponse());

        // "ok" value on first byte continue
        $binaryDataReader->advance(1);

        // decode all events data
        $eventInfo = $this->createEventInfo($binaryDataReader);

        $eventDTO = null;

        // always parse table map event but propagate when needed (we need this for creating table cache)
        if (ConstEventType::TABLE_MAP_EVENT === $eventInfo->getType()) {
            $eventDTO = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeTableMapDTO();
        }

        if (!Config::checkEvent($eventInfo->getType())) {
            return;
        }

        if (in_array($eventInfo->getType(), [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2], true)) {
            $eventDTO = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeUpdateRowsDTO();
        } elseif (in_array($eventInfo->getType(), [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2], true)) {
            $eventDTO = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeWriteRowsDTO();
        } elseif (in_array($eventInfo->getType(), [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2], true)) {
            $eventDTO = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeDeleteRowsDTO();
        } elseif (ConstEventType::XID_EVENT === $eventInfo->getType()) {
            $eventDTO = (new XidEvent($eventInfo, $binaryDataReader))->makeXidDTO();
        } elseif (ConstEventType::ROTATE_EVENT === $eventInfo->getType()) {
            $this->cache->clear();
            $eventDTO = (new RotateEvent($eventInfo, $binaryDataReader))->makeRotateEventDTO();
        } elseif (ConstEventType::GTID_LOG_EVENT === $eventInfo->getType()) {
            $eventDTO = (new GtidEvent($eventInfo, $binaryDataReader))->makeGTIDLogDTO();
        } elseif (ConstEventType::QUERY_EVENT === $eventInfo->getType()) {
            $eventDTO = (new QueryEvent($eventInfo, $binaryDataReader))->makeQueryDTO();
        } elseif (ConstEventType::MARIA_GTID_EVENT === $eventInfo->getType()) {
            $eventDTO = (new MariaDbGtidEvent($eventInfo, $binaryDataReader))->makeMariaDbGTIDLogDTO();
        } elseif (ConstEventType::FORMAT_DESCRIPTION_EVENT === $eventInfo->getType()) {
            $eventDTO = new FormatDescriptionEventDTO($eventInfo);
        } elseif (ConstEventType::HEARTBEAT_LOG_EVENT === $eventInfo->getType()) {
            $eventDTO = new HeartbeatDTO($eventInfo);
        }

        $this->dispatch($eventDTO);
    }

    /**
     * @param BinaryDataReader $binaryDataReader
     * @return EventInfo
     * @throws \MySQLReplication\BinaryDataReader\BinaryDataReaderException
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
            $this->socketConnect->getCheckSum(),
            $this->socketConnect->getBinLogCurrent()
        );
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
