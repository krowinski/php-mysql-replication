<?php

declare(strict_types=1);

namespace MySQLReplication\Definitions;

/**
 * @see https://github.com/mysql/mysql-server/blob/824e2b4064053f7daf17d7f3f84b7a3ed92e5fb4/libs/mysql/binlog/event/binlog_event.h#L285 (MySQL binlog_event.h)
 */
enum ConstEventType: int
{
    case MARIA_GTID_LIST_EVENT = 163;
    case INCIDENT_EVENT = 26;
    case IGNORABLE_LOG_EVENT = 28;
    case PRE_GA_UPDATE_ROWS_EVENT = 21;
    case HEARTBEAT_LOG_EVENT = 27;
    case GTID_LOG_EVENT = 33;
    case QUERY_EVENT = 2;
    case EXEC_LOAD_EVENT = 10;
    case PRE_GA_DELETE_ROWS_EVENT = 22;
    case MARIA_DELETE_ROWS_COMPRESSED_EVENT_V1 = 168;
    case MARIA_WRITE_ROWS_COMPRESSED_EVENT = 169;
    case DELETE_ROWS_EVENT_V2 = 32;
    case MARIA_WRITE_ROWS_COMPRESSED_EVENT_V1 = 166;
    case DELETE_ROWS_EVENT_V1 = 25;
    case MARIA_QUERY_COMPRESSED_EVENT = 165;
    case ROWS_QUERY_LOG_EVENT = 29;
    case MARIA_UPDATE_ROWS_COMPRESSED_EVENT_V1 = 167;
    case TABLE_MAP_EVENT = 19;
    case MARIA_START_ENCRYPTION_EVENT = 164;
    case MARIA_BINLOG_CHECKPOINT_EVENT = 161;
    case RAND_EVENT = 13;
    case XID_EVENT = 16;
    case PREVIOUS_GTIDS_LOG_EVENT = 35;
    case ROTATE_EVENT = 4;
    case DELETE_FILE_EVENT = 11;
    case BEGIN_LOAD_QUERY_EVENT = 17;
    case START_EVENT_V3 = 1;
    case SLAVE_EVENT = 7;
    case CREATE_FILE_EVENT = 8;
    case MARIA_DELETE_ROWS_COMPRESSED_EVENT = 171;
    case UPDATE_ROWS_EVENT_V1 = 24;
    case UPDATE_ROWS_EVENT_V2 = 31;
    case LOAD_EVENT = 6;
    case NEW_LOAD_EVENT = 12;
    case USER_VAR_EVENT = 14;
    case FORMAT_DESCRIPTION_EVENT = 15;
    case EXECUTE_LOAD_QUERY_EVENT = 18;
    case ANONYMOUS_GTID_LOG_EVENT = 34;
    case WRITE_ROWS_EVENT_V1 = 23;
    case WRITE_ROWS_EVENT_V2 = 30;
    case PRE_GA_WRITE_ROWS_EVENT = 20;
    case UNKNOWN_EVENT = 0;
    case APPEND_BLOCK_EVENT = 9;
    case STOP_EVENT = 3;
    case INTVAR_EVENT = 5;
    case MARIA_UPDATE_ROWS_COMPRESSED_EVENT = 170;
    case MARIA_GTID_EVENT = 162;

    //Transaction ID for 2PC, written whenever a COMMIT is expected.

    // Row-Based Binary Logging
    // TABLE_MAP_EVENT,WRITE_ROWS_EVENT
    // UPDATE_ROWS_EVENT,DELETE_ROWS_EVENT

    // MySQL 5.1.5 to 5.1.17,

    // MySQL 5.1.15 to 5.6.x

    // MySQL 5.6.x

    // mariadb
    // https://github.com/MariaDB/server/blob/10.4/sql/log_event.h
}
