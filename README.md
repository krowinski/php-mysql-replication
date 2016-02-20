php-mysql-replication
=========

Pure PHP Implementation of MySQL replication protocol. This allow you to receive event like insert, update, delete with their data and raw SQL queries.

Based on a great work of creators：https://github.com/noplay/python-mysql-replication and https://github.com/fengxiangyun/mysql-replication

Installation
=========

```sh
composer require php-mysql-replication
```

MySQL server settings
=========================

In your MySQL server configuration file you need to enable replication:

    [mysqld]
    server-id		 = 1
    log_bin			 = /var/log/mysql/mysql-bin.log
    expire_logs_days = 10
    max_binlog_size  = 100M
    binlog-format    = row #Very important if you want to receive write, update and delete row events

Examples
=========

All examples are available in the [examples directory](https://github.com/krowinski/php-mysql-replication/tree/master/example)

This example will dump all replication events to the console:

```php
<?php
error_reporting(E_ALL);
date_default_timezone_set('UTC');
ini_set('memory_limit', '8M');

include __DIR__ . '/../vendor/autoload.php';

use MySQLReplication\Service\BinLogStream;
use MySQLReplication\Config\Config;

$binLogStream = new BinLogStream(
    new Config('root', '192.168.1.100', 3306, 'root')
);
while (1)
{
    $result = $binLogStream->analysisBinLog();
    if (!is_null($result))
    {
        // all events got __toString() implementation
        echo $result;

        // all events got JsonSerializable implementation
        //echo json_encode($result, JSON_PRETTY_PRINT);

        //echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}
```

For this SQL sessions:

```sql
CREATE DATABASE php-mysql-replication;
use php-mysql-replication;
CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id));
INSERT INTO test4 (data,data2) VALUES ("Hello", "World");
UPDATE test4 SET data = "World", data2="Hello" WHERE id = 1;
DELETE FROM test4 WHERE id = 1;
```

Output will be similar to this:

    === MySQLReplication\DTO\GTIDLogDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46343
    Event size: 48
    Read bytes: 25
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:12984
    
    === MySQLReplication\DTO\QueryDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46488
    Event size: 145
    Read bytes: 122
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE DATABASE php_mysql_replication
    
    === MySQLReplication\DTO\GTIDLogDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46536
    Event size: 48
    Read bytes: 25
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:12985
    
    === MySQLReplication\DTO\QueryDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46751
    Event size: 215
    Read bytes: 192
    Database: php_mysql_replication
    Execution time: 0
    Query: CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id))
    
    === MySQLReplication\DTO\GTIDLogDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46799
    Event size: 48
    Read bytes: 25
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:12986
    
    === MySQLReplication\DTO\QueryDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46888
    Event size: 89
    Read bytes: 66
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    
    === MySQLReplication\DTO\TableMapDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 46959
    Event size: 71
    Read bytes: 48
    Table: test4
    Database: php_mysql_replication
    Table Id: 7794
    Columns: 3
    
    === MySQLReplication\DTO\WriteRowsDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47011
    Event size: 52
    Read bytes: 29
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
    
    
    === MySQLReplication\DTO\XidDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47042
    Event size: 31
    Read bytes: 8
    Transaction ID: 10153
    
    === MySQLReplication\DTO\GTIDLogDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47090
    Event size: 48
    Read bytes: 25
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:12987
    
    === MySQLReplication\DTO\QueryDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47179
    Event size: 89
    Read bytes: 66
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    
    === MySQLReplication\DTO\TableMapDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47250
    Event size: 71
    Read bytes: 48
    Table: test4
    Database: php_mysql_replication
    Table Id: 7794
    Columns: 3
    
    === MySQLReplication\DTO\UpdateRowsDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47320
    Event size: 70
    Read bytes: 47
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
    
    
    === MySQLReplication\DTO\XidDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47351
    Event size: 31
    Read bytes: 8
    Transaction ID: 10156
    
    === MySQLReplication\DTO\GTIDLogDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47399
    Event size: 48
    Read bytes: 25
    Commit: true
    GTID NEXT: 9b1c8d18-2a76-11e5-a26b-000c2976f3f3:12988
    
    === MySQLReplication\DTO\QueryDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47488
    Event size: 89
    Read bytes: 66
    Database: php_mysql_replication
    Execution time: 0
    Query: BEGIN
    
    === MySQLReplication\DTO\TableMapDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47559
    Event size: 71
    Read bytes: 48
    Table: test4
    Database: php_mysql_replication
    Table Id: 7794
    Columns: 3
    
    === MySQLReplication\DTO\DeleteRowsDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47611
    Event size: 52
    Read bytes: 29
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
    
    
    === MySQLReplication\DTO\XidDTO ===
    Date: 2016-02-20T18:33:17+00:00
    Log position: 47642
    Event size: 31
    Read bytes: 8
    Transaction ID: 10160

 
