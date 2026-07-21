<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MySQLReplication\Tests\Unit\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinLog\BinLogCurrent;
use MySQLReplication\Config\Config;
use MySQLReplication\Config\ConfigBuilder;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Definitions\ConstTableMapMetadataFieldType;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Event\RowEvent\TableMapBuilder;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\RepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TableMapBuilderTest extends TestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(RepositoryInterface::class);
    }

    public function testShouldBuildTableMapFromMetadataWithoutQueryingRepository(): void
    {
        $this->repository->expects(self::never())->method('getFields');

        $builder = $this->makeBuilder((new ConfigBuilder())->build());
        $reader = new BinaryDataReader($this->nullBitmap(2) . $this->makeMetadataBlob());

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        self::assertSame('test_db', $tableMap->database);
        self::assertSame('test_table', $tableMap->table);
        self::assertSame('1', $tableMap->tableId);
        self::assertSame(2, $tableMap->columnsAmount);

        $columns = $tableMap->columnDTOCollection;
        self::assertSame('id', $columns->offsetGet(0)->getName());
        self::assertFalse($columns->offsetGet(0)->isUnsigned());
        self::assertTrue($columns->offsetGet(0)->isPrimary());

        self::assertSame('amount', $columns->offsetGet(1)->getName());
        self::assertTrue($columns->offsetGet(1)->isUnsigned());
        self::assertFalse($columns->offsetGet(1)->isPrimary());
    }

    public function testShouldFallBackToRepositoryWhenMetadataDisabled(): void
    {
        $this->repository->expects(self::once())
            ->method('getFields')
            ->with('test_db', 'test_table')
            ->willReturn($this->makeFieldDTOCollection());

        $config = (new ConfigBuilder())->withUseTableMapMetadata(false)->build();
        $builder = $this->makeBuilder($config);
        $reader = new BinaryDataReader($this->nullBitmap(2) . $this->makeMetadataBlob());

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        $columns = $tableMap->columnDTOCollection;
        self::assertSame('id_from_schema', $columns->offsetGet(0)->getName());
        self::assertSame('amount_from_schema', $columns->offsetGet(1)->getName());
    }

    public function testShouldFallBackToRepositoryWhenNoMetadataBytesPresent(): void
    {
        $this->repository->expects(self::once())
            ->method('getFields')
            ->with('test_db', 'test_table')
            ->willReturn($this->makeFieldDTOCollection());

        $builder = $this->makeBuilder((new ConfigBuilder())->build());
        $reader = new BinaryDataReader($this->nullBitmap(2));

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        self::assertSame('id_from_schema', $tableMap->columnDTOCollection->offsetGet(0)->getName());
    }

    public function testShouldFallBackToRepositoryWhenMetadataHasNoColumnNames(): void
    {
        $this->repository->expects(self::once())
            ->method('getFields')
            ->with('test_db', 'test_table')
            ->willReturn($this->makeFieldDTOCollection());

        $builder = $this->makeBuilder((new ConfigBuilder())->build());
        $reader = new BinaryDataReader($this->nullBitmap(2) . $this->makeMinimalMetadataBlob());

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        $columns = $tableMap->columnDTOCollection;
        self::assertSame('id_from_schema', $columns->offsetGet(0)->getName());
        self::assertSame('amount_from_schema', $columns->offsetGet(1)->getName());
    }

    public function testShouldGuardAgainstColumnDroppedAfterBinlogWasWritten(): void
    {
        $this->repository->expects(self::once())->method('getFields')->willReturn(
            FieldDTOCollection::makeFromArray([
                [
                    'COLUMN_NAME' => 'id_from_schema',
                    'COLLATION_NAME' => null,
                    'CHARACTER_SET_NAME' => null,
                    'COLUMN_COMMENT' => '',
                    'COLUMN_TYPE' => 'int',
                    'COLUMN_KEY' => 'PRI',
                ],
            ])
        );

        $config = (new ConfigBuilder())->withUseTableMapMetadata(false)->build();
        $builder = $this->makeBuilder($config);
        $reader = new BinaryDataReader($this->nullBitmap(2));

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        $columns = $tableMap->columnDTOCollection;
        self::assertSame('id_from_schema', $columns->offsetGet(0)->getName());
        self::assertSame('DROPPED_COLUMN_1', $columns->offsetGet(1)->getName());
        self::assertSame(ConstFieldType::IGNORE, $columns->offsetGet(1)->type);
    }

    public function testShouldReturnEmptyColumnsWhenTableWasDropped(): void
    {
        $this->repository->expects(self::once())->method('getFields')->willReturn(new FieldDTOCollection());

        $config = (new ConfigBuilder())->withUseTableMapMetadata(false)->build();
        $builder = $this->makeBuilder($config);
        $reader = new BinaryDataReader($this->nullBitmap(2));

        $tableMap = $builder->build($reader, $this->makeEventInfo(), 'test_db', 'test_table', '1', 2, $this->columnTypes());

        self::assertTrue($tableMap->columnDTOCollection->isEmpty());
    }

    private function makeBuilder(Config $config): TableMapBuilder
    {
        return new TableMapBuilder($this->repository, $config, new NullLogger());
    }

    private function columnTypes(): string
    {
        return chr(ConstFieldType::LONG) . chr(ConstFieldType::LONG);
    }

    private function nullBitmap(int $columnsAmount): string
    {
        return str_repeat("\0", (int)(($columnsAmount + 7) / 8));
    }

    private function makeFieldDTOCollection(): FieldDTOCollection
    {
        return FieldDTOCollection::makeFromArray([
            [
                'COLUMN_NAME' => 'id_from_schema',
                'COLLATION_NAME' => null,
                'CHARACTER_SET_NAME' => null,
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'int',
                'COLUMN_KEY' => 'PRI',
            ],
            [
                'COLUMN_NAME' => 'amount_from_schema',
                'COLLATION_NAME' => null,
                'CHARACTER_SET_NAME' => null,
                'COLUMN_COMMENT' => '',
                'COLUMN_TYPE' => 'int unsigned',
                'COLUMN_KEY' => '',
            ],
        ]);
    }

    private function makeMetadataBlob(): string
    {
        $field = static fn (int $type, string $payload): string => chr($type) . chr(strlen($payload)) . $payload;

        // 2 numeric columns -> bits MSB-first: col0=0 (signed), col1=1 (unsigned)
        $signedness = $field(ConstTableMapMetadataFieldType::SIGNEDNESS, chr(0b01000000));

        $columnName = static fn (string $name): string => chr(strlen($name)) . $name;
        $columnNames = $field(
            ConstTableMapMetadataFieldType::COLUMN_NAME,
            $columnName('id') . $columnName('amount')
        );

        $simplePrimaryKey = $field(ConstTableMapMetadataFieldType::SIMPLE_PRIMARY_KEY, chr(0));

        return $signedness . $columnNames . $simplePrimaryKey;
    }

    private function makeMinimalMetadataBlob(): string
    {
        $field = static fn (int $type, string $payload): string => chr($type) . chr(strlen($payload)) . $payload;

        // binlog_row_metadata=MINIMAL only ever writes signedness, no COLUMN_NAME field
        return $field(ConstTableMapMetadataFieldType::SIGNEDNESS, chr(0b01000000));
    }

    private function makeEventInfo(): EventInfo
    {
        $binLogCurrent = new BinLogCurrent();
        $binLogCurrent->setBinFileName('binlog.001');
        $binLogCurrent->setBinLogPosition('0');

        return new EventInfo(1620000000, ConstEventType::QUERY_EVENT->value, 1, 100, '0', 0, false, $binLogCurrent);
    }
}
