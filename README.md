php-mysql-replication
=========
[![Build Status](https://travis-ci.org/krowinski/php-mysql-replication.svg?branch=master)](https://travis-ci.org/krowinski/php-mysql-replication)
[![Latest Stable Version](https://poser.pugx.org/krowinski/php-mysql-replication/v/stable)](https://packagist.org/packages/krowinski/php-mysql-replication) [![Total Downloads](https://poser.pugx.org/krowinski/php-mysql-replication/downloads)](https://packagist.org/packages/krowinski/php-mysql-replication) [![Latest Unstable Version](https://poser.pugx.org/krowinski/php-mysql-replication/v/unstable)](https://packagist.org/packages/krowinski/php-mysql-replication) 
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/4a0e49d4-3802-41d3-bb32-0a8194d0fd4d/mini.png)](https://insight.sensiolabs.com/projects/4a0e49d4-3802-41d3-bb32-0a8194d0fd4d) [![License](https://poser.pugx.org/krowinski/php-mysql-replication/license)](https://packagist.org/packages/krowinski/php-mysql-replication)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/krowinski/php-mysql-replication/?branch=master)

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

Configuration
=========

You can pass this array keys to ConfigService->makeConfigFromArray([]) or use ConfigBuilder to generate config.

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
 
'tableCacheSize' - some data are collected from information schema, this data is cached. This variable set cache for tables bigger takes more memory. (default 128 objects) 


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

Output will be similar to this:

    === Event gtid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021014
    Event size: 48
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:160837
    Memory usage 2.36 MB
    
    === Event query ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021159
    Event size: 145
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE DATABASE php_mysql_replication
    Memory usage 2.36 MB
    
    === Event gtid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021207
    Event size: 48
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:160838
    Memory usage 2.36 MB
    
    === Event query ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021422
    Event size: 215
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id))
    Memory usage 2.36 MB
    
    === Event gtid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021470
    Event size: 48
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:160839
    Memory usage 2.36 MB
    
    === Event query ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021559
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.36 MB
    
    === Event tableMap ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021630
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 1135
    Columns amount: 3
    Memory usage 2.36 MB
    
    === Event write ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021682
    Event size: 52
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
    
    Memory usage 2.37 MB
    
    === Event xid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021713
    Event size: 31
    Transaction ID: 252191
    Memory usage 2.37 MB
    
    === Event gtid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021761
    Event size: 48
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:160840
    Memory usage 2.37 MB
    
    === Event query ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021850
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.37 MB
    
    === Event tableMap ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021921
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 1135
    Columns amount: 3
    Memory usage 2.37 MB
    
    === Event update ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4021991
    Event size: 70
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
    
    Memory usage 2.37 MB
    
    === Event xid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022022
    Event size: 31
    Transaction ID: 252196
    Memory usage 2.37 MB
    
    === Event gtid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022070
    Event size: 48
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:160841
    Memory usage 2.37 MB
    
    === Event query ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022159
    Event size: 89
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    Memory usage 2.37 MB
    
    === Event tableMap ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022230
    Event size: 71
    Table: test4
    Database: php_mysql_replication
    Table Id: 1135
    Columns amount: 3
    Memory usage 2.37 MB
    
    === Event delete ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022282
    Event size: 52
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
    
    Memory usage 2.38 MB
    
    === Event xid ===
    Date: 2016-03-13T21:46:31+00:00
    Log position: 4022313
    Event size: 31
    Transaction ID: 252199
    Memory usage 2.38 MB


Benchmarks
=========
Tested on VM

Debian 8.3
PHP 5.6.17
MySQL 5.6.29-76.2-log Percona Server

```
inxi
```
    CPU~Dual core Intel Core i5-2500K (-MCP-) clocked at 3701 Mhz Kernel~3.16.0-4-amd64 x86_64 Up~2 days Mem~1170.9/3952.4MB HDD~41.9GB(15.2% used) Procs~119 Client~Shell inxi~2.1.28

```sh
php example/benchmark.php
```
    Start insert data
    6531 event by seconds (1000 total)
    6665 event by seconds (2000 total)
    6674 event by seconds (3000 total)
    6535 event by seconds (4000 total)
    6555 event by seconds (5000 total)
    6615 event by seconds (6000 total)
    6619 event by seconds (7000 total)
    6660 event by seconds (8000 total)
    6666 event by seconds (9000 total)
    6701 event by seconds (10000 total)
    6696 event by seconds (11000 total)
    6704 event by seconds (12000 total)

