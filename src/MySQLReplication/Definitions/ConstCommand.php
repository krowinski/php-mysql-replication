<?php

namespace MySQLReplication\Definitions;

class ConstCommand
{
    const COM_BINLOG_DUMP    = 0x12;
    const COM_REGISTER_SLAVE = 0x15;
    const COM_BINLOG_DUMP_GTID = 0x1e;
}