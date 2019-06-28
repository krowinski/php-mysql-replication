<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

class RotateDTO extends EventDTO
{
    private $position;
    private $nextBinlog;
    private $type = ConstEventsNames::ROTATE;

    public function __construct(
        EventInfo $eventInfo,
        int $position,
        string $nextBinlog
    ) {
        parent::__construct($eventInfo);

        $this->position = $position;
        $this->nextBinlog = $nextBinlog;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getNextBinlog(): string
    {
        return $this->nextBinlog;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return PHP_EOL .
            '=== Event ' . $this->getType() . ' === ' . PHP_EOL .
            'Date: ' . $this->eventInfo->getDateTime() . PHP_EOL .
            'Log position: ' . $this->eventInfo->getPos() . PHP_EOL .
            'Event size: ' . $this->eventInfo->getSize() . PHP_EOL .
            'Binlog position: ' . $this->position . PHP_EOL .
            'Binlog filename: ' . $this->nextBinlog . PHP_EOL;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}