<?php
declare(strict_types=1);

namespace MySQLReplication\BinLog;

use JsonSerializable;

class BinLogCurrent implements JsonSerializable
{
    /**
     * @var int
     */
    private $binLogPosition;
    /**
     * @var string
     */
    private $binFileName;
    /**
     * @var string
     */
    private $gtid;
    /**
     * @var string
     */
    private $mariaDbGtid;

    public function getBinLogPosition(): int
    {
        return $this->binLogPosition;
    }

    public function setBinLogPosition(int $binLogPosition): void
    {
        $this->binLogPosition = $binLogPosition;
    }

    public function getBinFileName(): string
    {
        return $this->binFileName;
    }

    public function setBinFileName(string $binFileName): void
    {
        $this->binFileName = $binFileName;
    }

    public function getGtid(): string
    {
        return $this->gtid;
    }

    public function setGtid(string $gtid): void
    {
        $this->gtid = $gtid;
    }

    public function getMariaDbGtid(): string
    {
        return $this->mariaDbGtid;
    }

    public function setMariaDbGtid(string $mariaDbGtid): void
    {
        $this->mariaDbGtid = $mariaDbGtid;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}