<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class XidDTO
 * @package MySQLReplication\Event\DTO
 */
class XidDTO extends EventDTO
{
    /**
     * @var string
     */
    private $type = ConstEventsNames::XID;
    /**
     * @var string
     */
    private $xid;

    /**
     * GTIDLogEventDTO constructor.
     * @param EventInfo $eventInfo
     * @param string $xid
     */
    public function __construct(
        EventInfo $eventInfo,
        string $xid
    ) {
        parent::__construct($eventInfo);

        $this->xid = $xid;
    }

    /**
     * @return string
     */
    public function getXid(): string
    {
        return $this->xid;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Transaction ID: ' . $this->xid . PHP_EOL;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}