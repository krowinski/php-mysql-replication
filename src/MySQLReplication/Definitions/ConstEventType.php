<?php
declare(strict_types=1);

namespace MySQLReplication\Definitions;

/**
 * @see https://dev.mysql.com/doc/internals/en/event-classes-and-types.html
 */
class ConstEventType
{
    public const UNKNOWN_EVENT = 0;
    public const START_EVENT_V3 = 1;
    public const QUERY_EVENT = 2;
    public const STOP_EVENT = 3;
    public const ROTATE_EVENT = 4;
    public const INTVAR_EVENT = 5;
    public const LOAD_EVENT = 6;
    public const SLAVE_EVENT = 7;
    public const CREATE_FILE_EVENT = 8;
    public const APPEND_BLOCK_EVENT = 9;
    public const EXEC_LOAD_EVENT = 10;
    public const DELETE_FILE_EVENT = 11;
    public const NEW_LOAD_EVENT = 12;
    public const RAND_EVENT = 13;
    public const USER_VAR_EVENT = 14;
    public const FORMAT_DESCRIPTION_EVENT = 15;

    //Transaction ID for 2PC, written whenever a COMMIT is expected.
    public const XID_EVENT = 16;
    public const BEGIN_LOAD_QUERY_EVENT = 17;
    public const EXECUTE_LOAD_QUERY_EVENT = 18;

    public const GTID_LOG_EVENT = 33;
    public const ANONYMOUS_GTID_LOG_EVENT = 34;
    public const PREVIOUS_GTIDS_LOG_EVENT = 35;

    public const INCIDENT_EVENT = 26;
    public const HEARTBEAT_LOG_EVENT = 27;
    public const IGNORABLE_LOG_EVENT = 28;
    public const ROWS_QUERY_LOG_EVENT = 29;

    // Row-Based Binary Logging
    // TABLE_MAP_EVENT,WRITE_ROWS_EVENT
    // UPDATE_ROWS_EVENT,DELETE_ROWS_EVENT
    public const TABLE_MAP_EVENT = 19;

    // MySQL 5.1.5 to 5.1.17,
    public const PRE_GA_WRITE_ROWS_EVENT = 20;
    public const PRE_GA_UPDATE_ROWS_EVENT = 21;
    public const PRE_GA_DELETE_ROWS_EVENT = 22;

    // MySQL 5.1.15 to 5.6.x
    public const WRITE_ROWS_EVENT_V1 = 23;
    public const UPDATE_ROWS_EVENT_V1 = 24;
    public const DELETE_ROWS_EVENT_V1 = 25;

    // MySQL 5.6.x
    public const WRITE_ROWS_EVENT_V2 = 30;
    public const UPDATE_ROWS_EVENT_V2 = 31;
    public const DELETE_ROWS_EVENT_V2 = 32;

    // mariadb
    // https://github.com/MariaDB/server/blob/10.4/sql/log_event.h
    public const MARIA_BINLOG_CHECKPOINT_EVENT = 161;
    public const MARIA_GTID_EVENT = 162;
    public const MARIA_GTID_LIST_EVENT = 163;
    public const MARIA_START_ENCRYPTION_EVENT = 164;
    public const MARIA_QUERY_COMPRESSED_EVENT = 165;
    public const MARIA_WRITE_ROWS_COMPRESSED_EVENT_V1 = 166;
    public const MARIA_UPDATE_ROWS_COMPRESSED_EVENT_V1 = 167;
    public const MARIA_DELETE_ROWS_COMPRESSED_EVENT_V1 = 168;
    public const MARIA_WRITE_ROWS_COMPRESSED_EVENT = 169;
    public const MARIA_UPDATE_ROWS_COMPRESSED_EVENT = 170;
    public const MARIA_DELETE_ROWS_COMPRESSED_EVENT = 171;
}