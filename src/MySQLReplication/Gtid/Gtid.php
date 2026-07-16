<?php

declare(strict_types=1);

namespace MySQLReplication\Gtid;

use MySQLReplication\BinaryDataReader\BinaryDataReader;

class Gtid
{
    private array $intervals = [];
    private string $sid;

    public function __construct(string $gtid)
    {
        if ((bool)preg_match('/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches) === false) {
            throw new GtidException(GtidException::INCORRECT_GTID_MESSAGE, GtidException::INCORRECT_GTID_CODE);
        }

        $this->sid = $matches[1];
        foreach (array_filter(explode(':', $matches[2]), static fn (string $part): bool => $part !== '') as $k) {
            $this->intervals[] = explode('-', $k);
        }
        $this->sid = str_replace('-', '', $this->sid);
    }

    public function getEncoded(): string
    {
        $buffer = pack('H*', $this->sid);
        $buffer .= BinaryDataReader::pack64bit(count($this->intervals));

        foreach ($this->intervals as $interval) {
            $buffer .= BinaryDataReader::pack64bit((int)$interval[0]);
            // MySQL GTID intervals are [start, stop) - stop is one past the last
            // included transaction number, not the inclusive endpoint.
            $stop = count($interval) !== 1 ? (int)$interval[1] : (int)$interval[0];
            $buffer .= BinaryDataReader::pack64bit($stop + 1);
        }

        return $buffer;
    }

    public function getEncodedLength(): int
    {
        return 40 * count($this->intervals);
    }
}
