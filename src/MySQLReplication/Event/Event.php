<?php

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReaderService;
use MySQLReplication\BinaryDataReader\Exception\BinaryDataReaderException;
use MySQLReplication\BinLog\BinLogConnect;
use MySQLReplication\BinLog\Exception\BinLogException;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\RowEvent\RowEventService;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Event
 * @package MySQLReplication\Event
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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * BinLogPack constructor.
     * @param Config $config
     * @param BinLogConnect $binLogConnect
     * @param BinaryDataReaderService $packageService
     * @param RowEventService $rowEventService
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        Config $config,
        BinLogConnect $binLogConnect,
        BinaryDataReaderService $packageService,
        RowEventService $rowEventService,
        EventDispatcher $eventDispatcher
    ) {
        $this->config = $config;
        $this->binLogConnect = $binLogConnect;
        $this->packageService = $packageService;
        $this->rowEventService = $rowEventService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws BinaryDataReaderException
     * @throws BinLogException
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
			$event = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeTableMapDTO();
			if ($event !== null) {
				$this->eventDispatcher->dispatch(ConstEventsNames::TABLE_MAP, $event);
			}
        } else {
            if ([] !== $this->config->getEventsOnly() && !in_array($eventInfo->getType(), $this->config->getEventsOnly(), true)) {
                return;
            }

            if (in_array($eventInfo->getType(), $this->config->getEventsIgnore(), true)) {
                return;
            }

            if (in_array($eventInfo->getType(), [ConstEventType::UPDATE_ROWS_EVENT_V1, ConstEventType::UPDATE_ROWS_EVENT_V2], true)) {
				$event = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeUpdateRowsDTO();
				if ($event !== null) {
					$this->eventDispatcher->dispatch(ConstEventsNames::UPDATE, $event);
				}
            } elseif (in_array($eventInfo->getType(), [ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2], true)) {
				$event = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeWriteRowsDTO();
				if ($event !== null) {
					$this->eventDispatcher->dispatch(ConstEventsNames::WRITE, $event);
				}
            } elseif (in_array($eventInfo->getType(), [ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2], true)) {
            	$event = $this->rowEventService->makeRowEvent($binaryDataReader, $eventInfo)->makeDeleteRowsDTO();
            	if ($event !== null) {
					$this->eventDispatcher->dispatch(ConstEventsNames::DELETE, $event);
				}
            } elseif (ConstEventType::XID_EVENT === $eventInfo->getType()) {
                $this->eventDispatcher->dispatch(ConstEventsNames::XID, (new XidEvent($eventInfo, $binaryDataReader))->makeXidDTO());
            } elseif (ConstEventType::ROTATE_EVENT === $eventInfo->getType()) {
                $this->eventDispatcher->dispatch(ConstEventsNames::ROTATE, (new RotateEvent($eventInfo, $binaryDataReader))->makeRotateEventDTO());
            } elseif (ConstEventType::GTID_LOG_EVENT === $eventInfo->getType()) {
                $this->eventDispatcher->dispatch(ConstEventsNames::GTID, (new GtidEvent($eventInfo, $binaryDataReader))->makeGTIDLogDTO());
            } elseif (ConstEventType::QUERY_EVENT === $eventInfo->getType()) {
                $this->eventDispatcher->dispatch(ConstEventsNames::QUERY, (new QueryEvent($eventInfo, $binaryDataReader))->makeQueryDTO());
            } elseif (ConstEventType::MARIA_GTID_EVENT === $eventInfo->getType()) {
                $this->eventDispatcher->dispatch(ConstEventsNames::MARIADB_GTID, (new MariaDbGtidEvent($eventInfo, $binaryDataReader))->makeMariaDbGTIDLogDTO());
            }
        }
    }
}
