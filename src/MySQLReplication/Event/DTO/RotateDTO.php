<?php
declare(strict_types=1);

namespace MySQLReplication\Event\DTO;

use MySQLReplication\Definitions\ConstEventsNames;
use MySQLReplication\Event\EventInfo;

/**
 * Class RotateDTO
 * @package MySQLReplication\DTO
 */
class RotateDTO extends EventDTO
{
    /**
     * @var int
     */
    private $position;
    /**
     * @var string
     */
    private $nextBinlog;
    /**
     * @var string
     */
    private $type = ConstEventsNames::ROTATE;

    /**
     * RotateDTO constructor.
     * @param EventInfo $eventInfo
     * @param $position
     * @param $nextBinlog
     */
    public function __construct(
        EventInfo $eventInfo,
        int $position,
        string $nextBinlog
    ) {
        parent::__construct($eventInfo);

        $this->position = $position;
        $this->nextBinlog = $nextBinlog;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getNextBinlog(): string
    {
        return $this->nextBinlog;
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
            'Binlog position: ' . $this->position . PHP_EOL .
            'Binlog filename: ' . $this->nextBinlog . PHP_EOL;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}