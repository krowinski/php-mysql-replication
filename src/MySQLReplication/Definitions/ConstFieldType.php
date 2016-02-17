<?php

namespace MySQLReplication\Definitions;

/**
 * Class ConstFieldType
 */
class ConstFieldType {

    const DECIMAL = 0;
    const TINY = 1;
    const SHORT = 2;
    const LONG = 3;
    const FLOAT = 4;
    const DOUBLE = 5;
    const NULL = 6;
    const TIMESTAMP = 7;
    const LONGLONG = 8;
    const INT24 = 9;
    const DATE = 10;
    const TIME = 11;
    const DATETIME = 12;
    const YEAR = 13;
    const NEWDATE = 14;
    const VARCHAR = 15;
    const BIT = 16;
    const TIMESTAMP2 = 17;
    const DATETIME2 = 18;
    const TIME2 = 19;
    const NEWDECIMAL = 246;
    const ENUM = 247;
    const SET = 248;
    const TINY_BLOB = 249;
    const MEDIUM_BLOB = 250;
    const LONG_BLOB = 251;
    const BLOB = 252;
    const VAR_STRING = 253;
    const STRING = 254;
    const GEOMETRY = 255;

    const CHAR = self::TINY;
    const INTERVAL = self::ENUM;

    const IGNORE = 666;
}