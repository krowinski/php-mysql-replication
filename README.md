# php-mysql-replication

based on a great work of：https://github.com/noplay/python-mysql-replication

## 运行环境
 目前只支持数据库utf8编码  
 php版本>=5.4  
 mysql版本>=5.5  
 需要安装php  sockets扩展  
 运行用户需要有创建文件的权限  

## Config.php 配置文件


运行run.php 目前只支持row模式  
项目中  可以用supervisor监控 run.php 进程  
Connect::analysisBinLog bool true存储当前的file  pos  
本例中 通过读取binlog存储到kafka中  kafka版本 0.8.2.0  
kafka-client 用到了github开源的一个项目  https://github.com/nmred/kafka-php  
BinLogPack.php打印了事件类型  


## 配置mysql，打开mysql的binlog，配置binlog格式为row
 log-bin=mysql-bin  
 server-id=1  
 binlog_format=row   

## 持久化
 file-pos 保存了当前读取到binlog的filename和pos，保证程序异常退出后能继续读取binlog  
 新项目运行时 要删除file-pos，从当前show master status,读取到的filename pos开始读取  
 可以设置file-pos，程序则从当前设置的位置读取binlog  
 
## 联系我
 任何问题可以mail  
 zhaozhiqiang1734@163.com  


