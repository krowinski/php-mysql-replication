#!/bin/bash

set -ex

if [ $DB == 'mysql:8.0' ]; then
    docker run -p 0.0.0.0:3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_ROOT_HOST=% --name=mysql -d ${DB} \
    mysqld \
      --datadir=/var/lib/mysql \
      --user=mysql \
      --server-id=1 \
      --log-bin=/var/lib/mysql/mysql-bin.log \
      --binlog-format=row \
      --max_allowed_packet=64M \
      --default_authentication_plugin=mysql_native_password
else
    docker run -p 0.0.0.0:3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_ROOT_HOST=% --name=mysql -d ${DB} \
    mysqld \
      --datadir=/var/lib/mysql \
      --user=mysql \
      --server-id=1 \
      --log-bin=/var/lib/mysql/mysql-bin.log \
      --binlog-format=row \
      --max_allowed_packet=64M
fi

mysql() {
  docker exec mysql mysql "${@}"
}
while :
do
  sleep 3
  mysql --protocol=tcp -e 'select version()' && break
done

docker logs mysql

