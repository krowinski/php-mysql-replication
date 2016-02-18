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
        var_dump($result);
        echo 'Memory usage ' . round(memory_get_usage() / 1048576, 2) . ' MB' . PHP_EOL;
    }
}
```

For this SQL sessions:

```sql
CREATE DATABASE test;
use test;
CREATE TABLE test4 (id int NOT NULL AUTO_INCREMENT, data VARCHAR(255), data2 VARCHAR(255), PRIMARY KEY(id));
INSERT INTO test4 (data,data2) VALUES ("Hello", "World");
UPDATE test4 SET data = "World", data2="Hello" WHERE id = 1;
DELETE FROM test4 WHERE id = 1;
```

 

