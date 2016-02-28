<?php

namespace MySQLReplication\Gtid;

/**
 * Class Gtid
 * @package MySQLReplication\Gtid
 */
class Gtid
{
    /**
     * @var array
     */
    private $intervals = [];
    /**
     * @var string
     */
    private $sid = '';

    /**
     * Gtid constructor.
     * @param $gtid
     * @throws GtidException
     */
    public function __construct($gtid)
    {
        if (false === (bool)preg_match('/^([0-9a-fA-F]{8}(?:-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12})((?::[0-9-]+)+)$/', $gtid, $matches))
        {
            throw new GtidException(GtidException::INCORRECT_GTID_MESSAGE, GtidException::INCORRECT_GTID_CODE);
        }

        $this->sid = $matches[1];
        foreach (array_filter(explode(':', $matches[2])) as $k)
        {
            $this->intervals[] = explode('-', $k);
        }
        $this->sid = str_replace('-', '', $this->sid);
    }

    /**
     * @return string
     */
    public function getEncoded()
    {
        $buffer = pack('H*', $this->sid);
        $buffer .= pack('Q', count($this->intervals));

        foreach ($this->intervals as $interval)
        {
            if (count($interval) != 1)
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[1]);
            }
            else
            {
                $buffer .= pack('Q', $interval[0]);
                $buffer .= pack('Q', $interval[0] + 1);
            }
        }

        return $buffer;
    }

    /**
     * @return int
     */
    public function getEncodedLength()
    {
        return (40 * count($this->intervals));
    }
}