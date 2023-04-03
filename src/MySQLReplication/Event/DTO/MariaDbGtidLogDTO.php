<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

class MariaDbGtidLogDTO extends EventDTO
{
    private $type = ConstEventsNames::MARIADB_GTID;
    private $flag;
    private $domainId;
    private $mariaDbGtid;

    public function __construct(
        EventInfo $eventInfo,
        int $flag,
        int $domainId,
        string $mariaDbGtid
    ) {
        parent::__construct($eventInfo);

        $this->flag = $flag;
        $this->domainId = $domainId;
        $this->mariaDbGtid = $mariaDbGtid;
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Flag: ' . var_export($this->flag, true) . PHP_EOL .
            'Domain Id: ' . $this->domainId . PHP_EOL .
            'Sequence Number: ' . $this->mariaDbGtid . PHP_EOL;
    }


    public function getType(): string
    {
        return $this->type;
    }


    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function getFlag(): int
    {
        return $this->flag;
    }

    public function getMariaDbGtid(): string
    {
        return $this->mariaDbGtid;
    }

    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
