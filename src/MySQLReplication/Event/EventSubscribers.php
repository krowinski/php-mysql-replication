<?php

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

/**
 * Class EventSubscribers
 * @package MySQLReplication\Event
 */
class EventSubscribers implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
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

    /**
     * @param UpdateRowsDTO $event
     */
    public function onUpdate(UpdateRowsDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event)
    {
    }

    /**
     * @param TableMapDTO $event
     */
    public function onTableMap(TableMapDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param DeleteRowsDTO $event
     */
    public function onDelete(DeleteRowsDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param GTIDLogDTO $event
     */
    public function onGTID(GTIDLogDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param QueryDTO $event
     */
    public function onQuery(QueryDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param RotateDTO $event
     */
    public function onRotate(RotateDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param XidDTO $event
     */
    public function onXID(XidDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param WriteRowsDTO $event
     */
    public function onWrite(WriteRowsDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param MariaDbGtidLogDTO $event
     */
    public function onMariaDbGtid(MariaDbGtidLogDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param FormatDescriptionEventDTO $event
     */
    public function onFormatDescription(FormatDescriptionEventDTO $event)
    {
        $this->allEvents($event);
    }

    /**
     * @param HeartbeatDTO $event
     */
    public function onHeartbeat(HeartbeatDTO $event)
    {
        $this->allEvents($event);
    }
}