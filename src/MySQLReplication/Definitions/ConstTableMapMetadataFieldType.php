<?php

declare(strict_types=1);

namespace MySQLReplication\Definitions;

/**
 * @see https://dev.mysql.com/blog-archive/more-metadata-is-written-into-binary-log/
 */
class ConstTableMapMetadataFieldType
{
    public const SIGNEDNESS = 1;
    public const DEFAULT_CHARSET = 2;
    public const COLUMN_CHARSET = 3;
    public const COLUMN_NAME = 4;
    public const SET_STR_VALUE = 5;
    public const ENUM_STR_VALUE = 6;
    public const GEOMETRY_TYPE = 7;
    public const SIMPLE_PRIMARY_KEY = 8;
    public const PRIMARY_KEY_WITH_PREFIX = 9;
    public const ENUM_AND_SET_DEFAULT_CHARSET = 10;
    public const ENUM_AND_SET_COLUMN_CHARSET = 11;
    public const VISIBILITY = 12;
}
