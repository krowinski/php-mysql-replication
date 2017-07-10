

# Release Notes

## v3.0.0 (2017-07-??)
- Added Cache interfaces for table info
- Changed examples to use ConfigBuilder
- Changed BinLogSocketConnect to separate sockets handling to another class + added interface for socket class
- Changed tests namespace to MySQLReplication
- Changed all exception messages moved to MySQLReplicationException
- Added CHANGELOG.md
- Simplify many classes and removed some of them 
- Added decorators for server version recognition
- Changed if datetime not recognised will return null (0000-00-00 00:00:00 is invalid date) 
- Added 'custom' param to config if some custom params must be set in extended/implemented own classes
- Added new tests
- Changed Repository $schema to $database
- Changed - YEAR = 0 will return null not 1900
- Removed Exception from Columns class
- Added format description event
- Changed inserts to not existing tables/columns will be returned as WriteEvent with empty Fields (see BasicTest::shouldGetWriteEventDropTable) 
- Changed TABLE_MAP_EVENT will no longer appear after adding events to only/ignore configuration 
- Fixed events with dropped columns will return a proper columns amount
- Changed configuration to static calls
- Removed absolute method getConnection from repository

## v2.2.0 (2017-03-10)
- Removed foreign keys from events 

## v2.1.3 (2016-11-26)
- Documentation update
- BinLogSocketConnect exception set as const
- 'Dbname' removed from configuration as is deprecated
- MySQLRepository and BinLogSocketConnect extracted to interfaces
- Register slave use now hostname and port to be correct display in "SHOW SLAVE HOSTS"
- Added foreign keys info to events

## v2.1.2 (2016-11-26)
- Added json decoder 16/32 int support
    
## v2.1.1 (2016-11-19)
- Fix for json decode
- Table cache option moved to config
- Strict variables 

## v2.1.0 (2016-11-05)
- Basic implementation of json binary encoder for mysql 5.7
- Config now support ip and host setting
- Connection_id correctly decoded
- Events dispatcher now can work with 2.8 lib
- Some code cleanup
- Added new tests

## v2.0.2 (2016-08-29)
- Added MariaDB compatibility 
- Code cleanup

## v2.0.1 (2016-08-28)
- Added new field support TIMESTAMP=7
- Query event fix
- Added db charset to db connection

## v2.0.0 (2016-08-26)
- Added MariaDb support
- Added symphony event dispatcher 
- Added db charset to db connection
- Removed support for php 5.4
- Added slave register

## v1.0.3 (2016-07-16)
- Added MariaDb gitid support (backport from 2.0.0-pre)

## v1.0.2 (2016-05-05)
- Fixed handling not existing value in enum definitions

## v1.0.1
- Fixed missing Config attr

## v1.0.0
- Added php5.4 compatibility 
- Added new results set 
- Added benchmark results to readme
- Added travis for tests