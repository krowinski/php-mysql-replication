<?php

namespace MySQLReplication\Definitions;

/**
 * Capability flags
 * http://dev.mysql.com/doc/internals/en/capability-flags.html#packet-protocol::capabilityflags
 * https://github.com/siddontang/mixer/blob/master/doc/protocol.txt
 */
class ConstCapabilityFlags
{
    const LONG_PASSWORD = 1;
    const FOUND_ROWS = 1 << 1;
    const LONG_FLAG = 1 << 2;
    const CONNECT_WITH_DB = 1 << 3;
    const NO_SCHEMA = 1 << 4;
    const COMPRESS = 1 << 5;
    const ODBC = 1 << 6;
    const LOCAL_FILES = 1 << 7;
    const IGNORE_SPACE = 1 << 8;
    const PROTOCOL_41 = 1 << 9;
    const INTERACTIVE = 1 << 10;
    const SSL = 1 << 11;
    const IGNORE_SIG_PIPE = 1 << 12;
    const TRANSACTIONS = 1 << 13;
    const SECURE_CONNECTION = 1 << 15;
    const MULTI_STATEMENTS = 1 << 16;
    const MULTI_RESULTS = 1 << 17;
    const CAPABILITIES = (self::LONG_PASSWORD | self::LONG_FLAG | self::TRANSACTIONS | self::PROTOCOL_41 | self::SECURE_CONNECTION);
}