<?php

declare(strict_types=1);

namespace MySQLReplication\Definitions;

class ConstFieldType
{
    public const DECIMAL = 0;
    public const TINY = 1;
    public const SHORT = 2;
    public const LONG = 3;
    public const FLOAT = 4;
    public const DOUBLE = 5;
    public const NULL = 6;
    public const TIMESTAMP = 7;
    public const LONGLONG = 8;
    public const INT24 = 9;
    public const DATE = 10;
    public const TIME = 11; // MySQL 5.5
    public const DATETIME = 12;
    public const YEAR = 13;
    public const NEWDATE = 14;
    public const VARCHAR = 15;
    public const BIT = 16;
    public const TIMESTAMP2 = 17;
    public const DATETIME2 = 18;
    public const TIME2 = 19;
    public const JSON = 245;
    public const NEWDECIMAL = 246;
    public const ENUM = 247;
    public const SET = 248;
    public const TINY_BLOB = 249;
    public const MEDIUM_BLOB = 250;
    public const LONG_BLOB = 251;
    public const BLOB = 252;
    public const VAR_STRING = 253;
    public const STRING = 254;
    public const GEOMETRY = 255;
    public const CHAR = 1;
    public const INTERVAL = 247;
    public const IGNORE = 666;
}
