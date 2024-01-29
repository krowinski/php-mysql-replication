<?php

declare(strict_types=1);

namespace MySQLReplication\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    private static array $tableMapCache = [];

    public function __construct(
        private readonly int $tableCacheSize = 0
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? self::$tableMapCache[$key] : $default;
    }

    public function has($key): bool
    {
        return isset(self::$tableMapCache[$key]);
    }

    public function clear(): bool
    {
        self::$tableMapCache = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $data[$key] = self::$tableMapCache[$key];
            }
        }

        return $data !== [] ? $data : (array)$default;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        // automatically clear table cache to save memory
        if (count(self::$tableMapCache) > $this->tableCacheSize) {
            self::$tableMapCache = array_slice(self::$tableMapCache, (int)($this->tableCacheSize / 2), null, true);
        }

        self::$tableMapCache[$key] = $value;

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function delete(string $key): bool
    {
        unset(self::$tableMapCache[$key]);

        return true;
    }
}
