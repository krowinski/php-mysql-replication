<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use Psr\SimpleCache\CacheInterface;

readonly class TableMapCache
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function get(string $tableId): TableMap
    {
        /** @var TableMap $tableMap */
        $tableMap = $this->cache->get($tableId);
        return $tableMap;
    }

    public function has(string $tableId): bool
    {
        return $this->cache->has($tableId);
    }

    public function set(string $tableId, TableMap $tableMap): void
    {
        $this->cache->set($tableId, $tableMap);
    }
}
