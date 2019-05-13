<?php
declare(strict_types=1);

namespace MySQLReplication\BinLog;

/**
 * Class BinLogCurrent
 * @package MySQLReplication\BinLog
 */
class BinLogCurrent implements \JsonSerializable
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

    /**
     * @return int
     */
    public function getBinLogPosition()
    {
        return $this->binLogPosition;
    }

    /**
     * @param int $binLogPosition
     */
    public function setBinLogPosition($binLogPosition)
    {
        $this->binLogPosition = $binLogPosition;
    }

    /**
     * @return string
     */
    public function getBinFileName()
    {
        return $this->binFileName;
    }

    /**
     * @param string $binFileName
     */
    public function setBinFileName($binFileName)
    {
        $this->binFileName = $binFileName;
    }

    /**
     * @return string
     */
    public function getGtid()
    {
        return $this->gtid;
    }

    /**
     * @param string $gtid
     */
    public function setGtid(string $gtid): void
    {
        $this->gtid = $gtid;
    }

    /**
     * @return string
     */
    public function getMariaDbGtid(): string
    {
        return $this->mariaDbGtid;
    }

    /**
     * @param string $mariaDbGtid
     */
    public function setMariaDbGtid(string $mariaDbGtid): void
    {
        $this->mariaDbGtid = $mariaDbGtid;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}