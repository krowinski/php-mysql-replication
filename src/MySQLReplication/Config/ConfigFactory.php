<?php

namespace MySQLReplication\Config;

/**
 * Class ConfigService
 * @package MySQLReplication\Config
 */
class ConfigFactory
{
    /**
     * @param array $config
     * @return Config
     */
    public function makeConfigFromArray(array $config)
    {
        $configBuilder = new ConfigBuilder();
        foreach ($config as $k => $v)
        {
            if ('user' === $k)
            {
                $configBuilder->withUser($v);
            }
            if ('ip' === $k || 'host' === $k)
            {
                $configBuilder->withHost($v);
            }
            if ('port' === $k)
            {
                $configBuilder->withPort($v);
            }
            if ('password' === $k)
            {
                $configBuilder->withPassword($v);
            }
            if ('charset' === $k)
            {
                $configBuilder->withCharset($v);
            }
            if ('gtid' === $k)
            {
                $configBuilder->withGtid($v);
            }
            if ('slaveId' === $k)
            {
                $configBuilder->withSlaveId($v);
            }
            if ('binLogFileName' === $k)
            {
                $configBuilder->withBinLogFileName($v);
            }
            if ('binLogPosition' === $k)
            {
                $configBuilder->withBinLogPosition($v);
            }
            if ('eventsOnly' === $k)
            {
                $configBuilder->withEventsOnly($v);
            }
            if ('eventsIgnore' === $k)
            {
                $configBuilder->withEventsIgnore($v);
            }
            if ('tablesOnly' === $k)
            {
                $configBuilder->withTablesOnly($v);
            }
            if ('databasesOnly' === $k)
            {
                $configBuilder->withDatabasesOnly($v);
            }
            if ('mariaDbGtid' === $k)
            {
                $configBuilder->withMariaDbGtid($v);
            }
            if ('tableCacheSize' === $k)
            {
                $configBuilder->withTableCacheSize($v);
            }
            if ('custom' === $k)
            {
                $configBuilder->withCustom($v);
            }
        }

        return $configBuilder->build();
    }
}