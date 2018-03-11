php-mysql-replication
=========
[![Build Status](https://travis-ci.org/krowinski/php-mysql-replication.svg?branch=master)](https://travis-ci.org/krowinski/php-mysql-replication)
[![Latest Stable Version](https://poser.pugx.org/krowinski/php-mysql-replication/v/stable)](https://packagist.org/packages/krowinski/php-mysql-replication) [![Total Downloads](https://poser.pugx.org/krowinski/php-mysql-replication/downloads)](https://packagist.org/packages/krowinski/php-mysql-replication) [![Latest Unstable Version](https://poser.pugx.org/krowinski/php-mysql-replication/v/unstable)](https://packagist.org/packages/krowinski/php-mysql-replication) 
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4a0e49d4-3802-41d3-bb32-0a8194d0fd4d/mini.png)](https://insight.sensiolabs.com/projects/4a0e49d4-3802-41d3-bb32-0a8194d0fd4d) [![License](https://poser.pugx.org/krowinski/php-mysql-replication/license)](https://packagist.org/packages/krowinski/php-mysql-replication)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/?branch=master)

Pure PHP Implementation of MySQL replication protocol. This allow you to receive event like insert, update, delete with their data and raw SQL queries.

Based on a great work of creators：https://github.com/noplay/python-mysql-replication and https://github.com/fengxiangyun/mysql-replication

Installation
=========

In you project

```sh
composer require krowinski/php-mysql-replication
```

or standalone 

```sh
git clone https://github.com/krowinski/php-mysql-replication.git

composer install -o
```

MySQL server settings
=========

In your MySQL server configuration file you need to enable replication:

    [mysqld]
    server-id		 = 1
    log_bin			 = /var/log/mysql/mysql-bin.log
    expire_logs_days = 10
    max_binlog_size  = 100M
    binlog-format    = row #Very important if you want to receive write, update and delete row events


Mysql replication events explained
    https://dev.mysql.com/doc/internals/en/event-meanings.html


Mysql user privileges:
```
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';

GRANT SELECT ON `dbName`.* TO 'user'@'host';
```

Configuration
=========

Use ConfigBuilder or ConfigFactory to create configuration.
Available options:

'user' - your mysql user (mandatory)

'ip' or 'host' - your mysql host/ip (mandatory)

'password' - your mysql password (mandatory)

'port' - your mysql host port (default 3306)

'charset' - db connection charset (default utf8)

'gtid' - GTID marker(s) to start from (format 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:1-177592)

'mariaDbGtid' - MariaDB GTID marker(s) to start from (format 1-1-3,0-1-88)

'slaveId' - script slave id for identification (SHOW SLAVE HOSTS)

'binLogFileName' - bin log file name to start from

'binLogPosition' - bin log position to start from 

'eventsOnly' - array  to listen on events (full list in [ConstEventType.php](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Definitions/ConstEventType.php) file)

'eventsIgnore' - array to ignore events (full list in [ConstEventType.php](https://github.com/krowinski/php-mysql-replication/blob/master/src/MySQLReplication/Definitions/ConstEventType.php) file) 

'tablesOnly' - array to only listen on given tables (default all tables) 

'databasesOnly' - array to only listen on given databases (default all databases) 
 
'tableCacheSize' - some data are collected from information schema, this data is cached.

'custom' - if some params must be set in extended/implemented own classes

'heartbeatPeriod' - sets the interval in seconds between replication heartbeats. Whenever the master's binary log is updated with an event, the waiting period for the next heartbeat is reset. interval is a decimal value having the range 0 to 4294967 seconds and a resolution in milliseconds; the smallest nonzero value is 0.001. Heartbeats are sent by the master only if there are no unsent events in the binary log file for a period longer than interval.

Examples
=========

All examples are available in the [examples directory](https://github.com/krowinski/php-mysql-replication/tree/master/example)

This example will dump all replication events to the console:

Remember to change config for your user, host and password.

User should have replication privileges [ REPLICATION CLIENT, SELECT]

```sh
php example/dump_events.php
```

For test SQL events:

```sql
CREATE DATABASE php_mysql_replication;
use php_mysql_replication;
CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id));
INSERT INTO test4 (data,data2) VALUES ("Hello", "World");
UPDATE test4 SET data = "World", data2="Hello" WHERE id = 1;
DELETE FROM test4 WHERE id = 1;
```

Output will be similar to this (depends on configuration for example GTID off/on):

    === Event format description ===
    Date: 2017-07-06T13:31:11+00:00
    Log position: 0
    Event size: 116
    Memory usage 2.4 MB
    
    === Event gtid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803092
    Event size: 48
    Commit: true
    GTID NEXT: 3403c535-624f-11e7-9940-0800275713ee:13675
    Memory usage 2.42 MB
    
    === Event query ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803237
    Event size: 145
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE DATABASE php_mysql_replication
    Memory usage 2.45 MB
    
    === Event gtid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803285
    Event size: 48
    Commit: true
    GTID NEXT: 3403c535-624f-11e7-9940-0800275713ee:13676
    Memory usage 2.45 MB
    
    === Event query ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803500
    Event size: 215
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id))
    Memory usage 2.45 MB
    
    === Event gtid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803548
    Event size: 48
    Commit: true
    GTID NEXT: 3403c535-624f-11e7-9940-0800275713ee:13677
    Memory usage 2.45 MB
    
    === Event query ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803637
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.45 MB
    
    === Event tableMap ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803708
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 866
    Columns amount: 3
    Memory usage 2.71 MB
    
    === Event write ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803762
    Event size: 54
    Table: test4
    Affected columns: 3
    Changed rows: 1
    Values: Array
    (
        [0] => Array
            (
                [id] => 1
                [data] => Hello
                [data2] => World
            )
    
    )
    
    Memory usage 2.74 MB
    
    === Event xid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803793
    Event size: 31
    Transaction ID: 662802
    Memory usage 2.75 MB
    
    === Event gtid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803841
    Event size: 48
    Commit: true
    GTID NEXT: 3403c535-624f-11e7-9940-0800275713ee:13678
    Memory usage 2.75 MB
    
    === Event query ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57803930
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.76 MB
    
    === Event tableMap ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804001
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 866
    Columns amount: 3
    Memory usage 2.75 MB
    
    === Event update ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804075
    Event size: 74
    Table: test4
    Affected columns: 3
    Changed rows: 1
    Values: Array
    (
        [0] => Array
            (
                [before] => Array
                    (
                        [id] => 1
                        [data] => Hello
                        [data2] => World
                    )
    
                [after] => Array
                    (
                        [id] => 1
                        [data] => World
                        [data2] => Hello
                    )
    
            )
    
    )
    
    Memory usage 2.76 MB
    
    === Event xid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804106
    Event size: 31
    Transaction ID: 662803
    Memory usage 2.76 MB
    
    === Event gtid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804154
    Event size: 48
    Commit: true
    GTID NEXT: 3403c535-624f-11e7-9940-0800275713ee:13679
    Memory usage 2.76 MB
    
    === Event query ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804243
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.76 MB
    
    === Event tableMap ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804314
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 866
    Columns amount: 3
    Memory usage 2.76 MB
    
    === Event delete ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804368
    Event size: 54
    Table: test4
    Affected columns: 3
    Changed rows: 1
    Values: Array
    (
        [0] => Array
            (
                [id] => 1
                [data] => World
                [data2] => Hello
            )
    
    )
    
    Memory usage 2.77 MB
    
    === Event xid ===
    Date: 2017-07-06T15:23:44+00:00
    Log position: 57804399
    Event size: 31
    Transaction ID: 662804
    Memory usage 2.77 MB



Benchmarks
=========
Tested on VM

    Debian 8.7
    PHP 5.6.30
    Percona 5.6.35

```sh
inxi
```

    CPU(s)~4 Single core Intel Core i5-2500Ks (-SMP-) clocked at 5901 Mhz Kernel~3.16.0-4-amd64 x86_64 Up~1 day Mem~1340.3/1996.9MB HDD~41.9GB(27.7% used) Procs~122 Client~Shell inxi~2.1.28

```sh
php example/benchmark.php
```
    Start insert data
    7442 event by seconds (1000 total)
    7679 event by seconds (2000 total)
    7914 event by seconds (3000 total)
    7904 event by seconds (4000 total)
    7965 event by seconds (5000 total)
    8006 event by seconds (6000 total)
    8048 event by seconds (7000 total)
    8038 event by seconds (8000 total)
    8040 event by seconds (9000 total)
    8055 event by seconds (10000 total)
    8058 event by seconds (11000 total)
    8071 event by seconds (12000 total)

