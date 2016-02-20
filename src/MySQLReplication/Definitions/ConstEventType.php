<?php

namespace MySQLReplication\Definitions;

/**
 * Class ConstEventType
 * @package MySQLReplication\Definitions
 */
class ConstEventType
{
    const UNKNOWN_EVENT = 0;
    const START_EVENT_V3 = 1;
    const QUERY_EVENT = 2;
    const STOP_EVENT = 3;
    const ROTATE_EVENT = 4;
    const INTVAR_EVENT = 5;
    const LOAD_EVENT = 6;
    const SLAVE_EVENT = 7;
    const CREATE_FILE_EVENT = 8;
    const APPEND_BLOCK_EVENT = 9;
    const EXEC_LOAD_EVENT = 10;
    const DELETE_FILE_EVENT = 11;
    const NEW_LOAD_EVENT = 12;
    const RAND_EVENT = 13;
    const USER_VAR_EVENT = 14;
    const FORMAT_DESCRIPTION_EVENT = 15;

    //Transaction ID for 2PC, written whenever a COMMIT is expected.
    const XID_EVENT = 16;
    const BEGIN_LOAD_QUERY_EVENT = 17;
    const EXECUTE_LOAD_QUERY_EVENT = 18;

    const GTID_LOG_EVENT = 33;
    const ANONYMOUS_GTID_LOG_EVENT = 34;
    const PREVIOUS_GTIDS_LOG_EVENT = 35;

    const INCIDENT_EVENT = 26;
    const HEARTBEAT_LOG_EVENT = 27;
    const IGNORABLE_LOG_EVENT = 28;
    const ROWS_QUERY_LOG_EVENT = 29;

    // Row-Based Binary Logging
    // TABLE_MAP_EVENT,WRITE_ROWS_EVENT
    // UPDATE_ROWS_EVENT,DELETE_ROWS_EVENT
    const TABLE_MAP_EVENT = 19;

    // MySQL 5.1.5 to 5.1.17,
    const PRE_GA_WRITE_ROWS_EVENT = 20;
    const PRE_GA_UPDATE_ROWS_EVENT = 21;
    const PRE_GA_DELETE_ROWS_EVENT = 22;

    // MySQL 5.1.15 to 5.6.x
    const WRITE_ROWS_EVENT_V1 = 23;
    const UPDATE_ROWS_EVENT_V1 = 24;
    const DELETE_ROWS_EVENT_V1 = 25;

    // MySQL 5.6.x
    const WRITE_ROWS_EVENT_V2 = 30;
    const UPDATE_ROWS_EVENT_V2 = 31;
    const DELETE_ROWS_EVENT_V2 = 32;
}
