<?php

declare(strict_types=1);

namespace MySQLReplication;

class Tools
{
    public static function getFromEnv(string $name, null|int|string $default = null): null|int|string
    {
        $value = getenv($name) ?: null;
        return $value ?? $default;
    }
}
