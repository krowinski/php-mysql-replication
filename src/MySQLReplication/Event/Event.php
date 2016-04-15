<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use MySQLReplication\BinLog\BinLogConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\GTIDLogDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use MySQLReplication\Event\RowEvent\RowEventService;

/**
 * Class Event
 */
class Event
{
    /**
     * @var BinLogConnect
     */
    private $binLogConnect;
    /**
     * @var BinaryDataReaderService
     */
    private $packageService;
    /**
     * @var RowEventService
     */
    private $rowEventService;
    /**
     * @var Config
     */
    private $config;

    /**
     * BinLogPack constructor.
     * @param Config $config
     * @param BinLogConnect $binLogConnect
     * @param BinaryDataReaderService $packageService
     * @param RowEventService $rowEventService
     */
    public function __construct(
        Config $config,
        BinLogConnect $binLogConnect,
        BinaryDataReaderService $packageService,
        RowEventService $rowEventService
    ) {
        $this->config = $config;
        $this->binLogConnect = $binLogConnect;
        $this->packageService = $packageService;
        $this->rowEventService = $rowEventService;
    }

    /**
     * @return DeleteRowsDTO|EventDTO|GTIDLogDTO|QueryDTO|RotateDTO|TableMapDTO|UpdateRowsDTO|WriteRowsDTO|XidDTO
     */
    public function consume()
    {
        $binaryDataReader = $this->packageService->makePackageFromBinaryData(
            $this->binLogConnect->getPacket(false)
        );

        // "ok" value on first byte continue
        $binaryDataReader->advance(1);

        // decode all events data
        $eventInfo = new EventInfo(
            $binaryDataReader->readInt32(),
            $binaryDataReader->readUInt8(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readInt32(),
            $binaryDataReader->readUInt16(),
            $this->binLogConnect->getCheckSum()
        );

        if (ConstEventType::TABLE_MAP_EVENT === $eventInfo->getType()) {
            $rowEvent = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo);

            return $rowEvent->makeTableMapDTO();
        }

        if ([] !== $this->config->getEventsOnly() && !in_array($eventInfo->getType(), $this->config->getEventsOnly(), true)) {
            return null;
        }

        if (in_array($eventInfo->getType(), $this->config->getEventsIgnore(), true)) {
            return null;
        }

        if (in_array($eventInfo->getType(), [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2], true)) {
            $rowEvent = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo);

            return $rowEvent->makeUpdateRowsDTO();
        } elseif (in_array($eventInfo->getType(), [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2], true)) {
            $rowEvent = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo);

            return $rowEvent->makeWriteRowsDTO();
        } elseif (in_array($eventInfo->getType(), [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2], true)) {
            $rowEvent = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo);

            return $rowEvent->makeDeleteRowsDTO();
        } elseif (ConstEventType::XID_EVENT === $eventInfo->getType()) {
            return (new XidEvent($eventInfo, $binaryDataReader))->makeXidDTO();
        } elseif (ConstEventType::ROTATE_EVENT === $eventInfo->getType()) {
            return (new RotateEvent($eventInfo, $binaryDataReader))->makeRotateEventDTO();
        } elseif (ConstEventType::GTID_LOG_EVENT === $eventInfo->getType()) {
            return (new GtidEvent($eventInfo, $binaryDataReader))->makeGTIDLogDTO();
        } else if (ConstEventType::QUERY_EVENT === $eventInfo->getType()) {
            return (new QueryEvent($eventInfo, $binaryDataReader))->makeQueryDTO();
        }

        return null;
    }
}
