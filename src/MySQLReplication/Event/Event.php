<?php

declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogServerInfo;
use MySQLReplication\BinLog\BinLogSocketConnect;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\RowEvent\RowEventFactory;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class Event
{
    private const MARIADB_DUMMY_QUERY = '# Dum';

    private const EOF_HEADER_VALUE = 254;

    public function __construct(
        private BinLogSocketConnect $binLogSocketConnect,
        private RowEventFactory $rowEventFactory,
        private EventDispatcherInterface $eventDispatcher,
        private CacheInterface $cache,
        private Config $config,
        private BinLogServerInfo $binLogServerInfo
    ) {
    }

    public function consume(): void
    {
        $binaryDataReader = new BinaryDataReader($this->binLogSocketConnect->getResponse());

        // check EOF_Packet -> https://dev.mysql.com/doc/internals/en/packet-EOF_Packet.html
        if ($binaryDataReader->readUInt8() === self::EOF_HEADER_VALUE) {
            return;
        }

        $this->dispatch($this->makeEvent($binaryDataReader));
    }

    private function makeEvent(BinaryDataReader $binaryDataReader): ?EventDTO
    {
        // decode all events data
        $eventInfo = $this->createEventInfo($binaryDataReader);

        // we always need these events to clean table maps and for BinLogCurrent class to keep track of binlog position
        // always parse table map event but propagate when needed (we need this for creating table cache)
        if ($eventInfo->type === ConstEventType::TABLE_MAP_EVENT->value) {
            return $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)
                ->makeTableMapDTO();
        }

        if ($eventInfo->type === ConstEventType::ROTATE_EVENT->value) {
            $this->cache->clear();
            return (new RotateEvent($eventInfo, $binaryDataReader, $this->binLogServerInfo))->makeRotateEventDTO();
        }

        if ($eventInfo->type === ConstEventType::GTID_LOG_EVENT->value) {
            return (new GtidEvent($eventInfo, $binaryDataReader, $this->binLogServerInfo))->makeGTIDLogDTO();
        }

        if ($eventInfo->type === ConstEventType::HEARTBEAT_LOG_EVENT->value) {
            return new HeartbeatDTO($eventInfo);
        }

        if ($eventInfo->type === ConstEventType::MARIA_GTID_EVENT->value) {
            return (new MariaDbGtidEvent(
                $eventInfo,
                $binaryDataReader,
                $this->binLogServerInfo
            ))->makeMariaDbGTIDLogDTO();
        }

        // check for ignore and permitted events
        if ($this->ignoreEvent($eventInfo->type)) {
            return null;
        }

        if (in_array(
            $eventInfo->type,
            [ConstEventType::UPDATE_ROWS_EVENT_V1->value, ConstEventType::UPDATE_ROWS_EVENT_V2->value],
            true
        )) {
            return $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)
                ->makeUpdateRowsDTO();
        }

        if (in_array(
            $eventInfo->type,
            [ConstEventType::WRITE_ROWS_EVENT_V1->value, ConstEventType::WRITE_ROWS_EVENT_V2->value],
            true
        )) {
            return $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)
                ->makeWriteRowsDTO();
        }

        if (in_array(
            $eventInfo->type,
            [ConstEventType::DELETE_ROWS_EVENT_V1->value, ConstEventType::DELETE_ROWS_EVENT_V2->value],
            true
        )) {
            return $this->rowEventFactory->makeRowEvent($binaryDataReader, $eventInfo)
                ->makeDeleteRowsDTO();
        }

        if ($eventInfo->type === ConstEventType::XID_EVENT->value) {
            return (new XidEvent($eventInfo, $binaryDataReader, $this->binLogServerInfo))->makeXidDTO();
        }

        if ($eventInfo->type === ConstEventType::QUERY_EVENT->value) {
            return $this->filterDummyMariaDbEvents(
                (new QueryEvent($eventInfo, $binaryDataReader, $this->binLogServerInfo))->makeQueryDTO()
            );
        }

        if ($eventInfo->type === ConstEventType::FORMAT_DESCRIPTION_EVENT->value) {
            return new FormatDescriptionEventDTO($eventInfo);
        }

        return null;
    }

    private function createEventInfo(BinaryDataReader $binaryDataReader): EventInfo
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

    private function filterDummyMariaDbEvents(QueryDTO $queryDTO): ?QueryDTO
    {
        if ($this->binLogServerInfo->isMariaDb() && str_contains($queryDTO->query, self::MARIADB_DUMMY_QUERY)) {
            return null;
        }

        return $queryDTO;
    }

    private function dispatch(?EventDTO $eventDTO): void
    {
        if ($eventDTO) {
            if ($this->ignoreEvent($eventDTO->getEventInfo()->type)) {
                return;
            }
            $this->eventDispatcher->dispatch($eventDTO, $eventDTO->getType());
        }
    }

    private function ignoreEvent(int $type): bool
    {
        return !$this->config->checkEvent($type);
    }
}
