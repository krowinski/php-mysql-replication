<?php
declare(strict_types=1);

namespace MySQLReplication\Cache;

use MySQLReplication\Config\Config;
use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    private static $tableMapCache = [];

    public static string $rawQuery = '';

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? self::$tableMapCache[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return isset(self::$tableMapCache[$key]);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        self::$tableMapCache = [];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $data = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $data[$key] = self::$tableMapCache[$key];
            }
        }

        return [] !== $data ? $data : $default;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        // automatically clear table cache to save memory
        if (count(self::$tableMapCache) > Config::getTableCacheSize()) {
            self::$tableMapCache = array_slice(
                self::$tableMapCache,
                (int)(Config::getTableCacheSize() / 2),
                null,
                true
            );
        }

        self::$tableMapCache[$key] = $value;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        unset(self::$tableMapCache[$key]);

        return true;
    }

    public static function getRawQuery(): string
    {
        return self::$rawQuery;
    }

    public static function setRawQuery(string $rawQuery): void
    {
        self::$rawQuery = $rawQuery;
    }
}
