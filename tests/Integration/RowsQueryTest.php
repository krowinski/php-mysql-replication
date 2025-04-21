<?php

declare(strict_types=1);

namespace MySQLReplication\Tests\Integration;

use Generator;
use MySQLReplication\Definitions\ConstEventType;
use MySQLReplication\Event\DTO\QueryDTO;
use MySQLReplication\Event\DTO\RowsQueryDTO;
use PHPUnit\Framework\Attributes\DataProvider;

final class RowsQueryTest extends BaseCase
{
    #[DataProvider('provideQueries')]
    public function testThatTheEditingQueryIsReadFromBinLog(string $query): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE test (id INT NOT NULL AUTO_INCREMENT, data VARCHAR (50) NOT NULL, PRIMARY KEY (id))'
        );

        $this->connection->executeStatement($query);

        // The Create Table Query ... irrelevant content for this test
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());
        // The BEGIN Query ... irrelevant content for this test
        self::assertInstanceOf(QueryDTO::class, $this->getEvent());

        $rowsQueryEvent = $this->getEvent();
        self::assertInstanceOf(RowsQueryDTO::class, $rowsQueryEvent);
        self::assertSame($query, $rowsQueryEvent->query);
    }

    public static function provideQueries(): Generator
    {
        yield 'Short Query' => ['INSERT INTO test (data) VALUES(\'Hello\') /* Foo:Bar; */'];

        $comment = '/* Foo:Bar; Bar:Baz; Baz:Quo; Quo:Foo; Quo:Foo; Quo:Foo; Quo:Foo; Foo:Baz; */';
        yield 'Extra Long Query' => [$comment . ' INSERT INTO test (data) VALUES(\'Hello\') ' . $comment];
    }
    
    protected function getIgnoredEvents(): array
    {
        return [ConstEventType::GTID_LOG_EVENT->value];
    }
}
