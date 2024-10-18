<?php
declare(strict_types=1);

namespace MySQLReplication\Event;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\DTO\DeleteRowsDTO;
use MySQLReplication\Event\DTO\EventDTO;
use MySQLReplication\Event\DTO\FormatDescriptionEventDTO;
use MySQLReplication\Event\DTO\GTIDLogDTO;
use MySQLReplication\Event\DTO\HeartbeatDTO;
use MySQLReplication\Event\DTO\MariaDbGtidLogDTO;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RotateDTO;
use MySQLReplication\Event\DTO\TableMapDTO;
use MySQLReplication\Event\DTO\UpdateRowsDTO;
use MySQLReplication\Event\DTO\WriteRowsDTO;
use MySQLReplication\Event\DTO\XidDTO;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscribers implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConstEventsNames::TABLE_MAP => 'onTableMap',
            ConstEventsNames::UPDATE => 'onUpdate',
            ConstEventsNames::DELETE => 'onDelete',
            ConstEventsNames::GTID => 'onGTID',
            ConstEventsNames::QUERY => 'onQuery',
            ConstEventsNames::ROTATE => 'onRotate',
            ConstEventsNames::XID => 'onXID',
            ConstEventsNames::WRITE => 'onWrite',
            ConstEventsNames::MARIADB_GTID => 'onMariaDbGtid',
            ConstEventsNames::FORMAT_DESCRIPTION => 'onFormatDescription',
            ConstEventsNames::HEARTBEAT => 'onHeartbeat',
        ];
    }

    public function onUpdate(UpdateRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    protected function allEvents(EventDTO $event): void
    {
    }

    public function onTableMap(TableMapDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onDelete(DeleteRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onGTID(GTIDLogDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onQuery(QueryDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onRotate(RotateDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onXID(XidDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onWrite(WriteRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onMariaDbGtid(MariaDbGtidLogDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onFormatDescription(FormatDescriptionEventDTO $event): void
    {
        $this->allEvents($event);
    }

    public function onHeartbeat(HeartbeatDTO $event): void
    {
        $this->allEvents($event);
    }
}