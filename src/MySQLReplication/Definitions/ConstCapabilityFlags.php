<?php

namespace MySQLReplication\Definitions;

/**
 * Capability flags
 * http://dev.mysql.com/doc/internals/en/capability-flags.html#packet-protocol::capabilityflags
 * https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
 */
class ConstCapabilityFlags
{
    public static function getCapabilities()
    {
        /*
            Left only as information
            $foundRows = 1 << 1;
            $connectWithDb = 1 << 3;
            $compress = 1 << 5;
            $odbc = 1 << 6;
            $localFiles = 1 << 7;
            $ignoreSpace = 1 << 8;
            $multiStatements = 1 << 16;
            $multiResults = 1 << 17;
            $interactive = 1 << 10;
            $ssl = 1 << 11;
            $ignoreSigPipe = 1 << 12;
        */

        $noSchema = 1 << 4;
        $longPassword = 1;
        $longFlag = 1 << 2;
        $transactions = 1 << 13;
        $secureConnection = 1 << 15;
        $protocol41 = 1 << 9;

        return ($longPassword | $longFlag | $transactions | $protocol41 | $secureConnection | $noSchema);
    }
}