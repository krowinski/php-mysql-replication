# php-mysql-replication

参考python版本：https://github.com/noplay/python-mysql-replication

## 运行环境
 php版本>=5.4  
 mysql版本>=5.5  
 需要安装php  sockets扩展  
 运行用户需要有创建文件的权限  

## Config.php 配置文件


运行run.php 目前只支持row模式
Connect::analysisBinLog bool true存储当前的file  pos
本例中 通过读取binlog存储到kafka中
kafka-client 用不到了github开源的一个项目  https://github.com/nmred/kafka-php
BinLogPack.php打印了事件类型


## 配置mysql，打开mysql的binlog，配置binlog格式为row
 log-bin=mysql-bin  
 server-id=1  
 binlog_format=row   


