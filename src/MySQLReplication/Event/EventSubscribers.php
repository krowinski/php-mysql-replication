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

/**
 * Class EventSubscribers
 * @package MySQLReplication\Event
 */
class EventSubscribers implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
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

    /**
     * @param UpdateRowsDTO $event
     */
    public function onUpdate(UpdateRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param EventDTO $event
     */
    protected function allEvents(EventDTO $event): void
    {
    }

    /**
     * @param TableMapDTO $event
     */
    public function onTableMap(TableMapDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param DeleteRowsDTO $event
     */
    public function onDelete(DeleteRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param GTIDLogDTO $event
     */
    public function onGTID(GTIDLogDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param QueryDTO $event
     */
    public function onQuery(QueryDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param RotateDTO $event
     */
    public function onRotate(RotateDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param XidDTO $event
     */
    public function onXID(XidDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param WriteRowsDTO $event
     */
    public function onWrite(WriteRowsDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param MariaDbGtidLogDTO $event
     */
    public function onMariaDbGtid(MariaDbGtidLogDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param FormatDescriptionEventDTO $event
     */
    public function onFormatDescription(FormatDescriptionEventDTO $event): void
    {
        $this->allEvents($event);
    }

    /**
     * @param HeartbeatDTO $event
     */
    public function onHeartbeat(HeartbeatDTO $event): void
    {
        $this->allEvents($event);
    }
}