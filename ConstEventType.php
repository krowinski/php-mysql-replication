<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/16
 * Time: 上午11:36
 */
class ConstEventType {

    const UNKNOWN_EVENT    = 0,
    START_EVENT_V3         = 1,
    QUERY_EVENT= 2,
    STOP_EVENT= 3,
    ROTATE_EVENT= 4,
    INTVAR_EVENT= 5,
    LOAD_EVENT= 6,
    SLAVE_EVENT= 7,
    CREATE_FILE_EVENT= 8,
    APPEND_BLOCK_EVENT= 9,
    EXEC_LOAD_EVENT= 10,
    DELETE_FILE_EVENT= 11,
    NEW_LOAD_EVENT= 12,
    RAND_EVENT= 13,
    USER_VAR_EVENT= 14,
    FORMAT_DESCRIPTION_EVENT= 15;

        //Transaction ID for 2PC, written whenever a COMMIT is expected.
    const XID_EVENT= 16,
    BEGIN_LOAD_QUERY_EVENT= 17,
    EXECUTE_LOAD_QUERY_EVENT= 18;

    const GTID_LOG_EVENT= 33;
    const ANONYMOUS_GTID_LOG_EVENT= 34;
    const PREVIOUS_GTIDS_LOG_EVENT= 35;

    const INCIDENT_EVENT       = 26;
    const HEARTBEAT_LOG_EVENT  = 27;
    const IGNORABLE_LOG_EVENT  = 28;
    const ROWS_QUERY_LOG_EVENT = 29;

    // Row-Based Binary Logging
    // TABLE_MAP_EVENT,WRITE_ROWS_EVENT
    // UPDATE_ROWS_EVENT,DELETE_ROWS_EVENT
    const TABLE_MAP_EVENT          = 19;

    // MySQL 5.1.5 to 5.1.17,
    const PRE_GA_WRITE_ROWS_EVENT  = 20;
    const PRE_GA_UPDATE_ROWS_EVENT = 21;
    const PRE_GA_DELETE_ROWS_EVENT = 22;

    // MySQL 5.1.15 to 5.6.x
    const WRITE_ROWS_EVENT_V1  = 23;
    const UPDATE_ROWS_EVENT_V1 = 24;
    const DELETE_ROWS_EVENT_V1 = 25;

    // MySQL 5.6.x
    const WRITE_ROWS_EVENT_V2  = 30;
    const UPDATE_ROWS_EVENT_V2 = 31;
    const DELETE_ROWS_EVENT_V2 = 32;


}
