<?php

declare(strict_types=1);

namespace MySQLReplication\Event\RowEvent;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\Config\Config;
use MySQLReplication\Definitions\ConstFieldType;
use MySQLReplication\Event\EventInfo;
use MySQLReplication\Repository\FieldDTO;
use MySQLReplication\Repository\FieldDTOCollection;
use MySQLReplication\Repository\RepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Builds the TableMap for a TABLE_MAP_EVENT: reads the per-column structural metadata,
 * then resolves column names/unsigned/ENUM-SET/primary-key info either from the event's own
 * metadata fields (binlog_row_metadata=FULL) or, when that's absent, from information_schema.
 */
readonly class TableMapBuilder
{
    public function __construct(
        private RepositoryInterface $repository,
        private Config $config,
        private LoggerInterface $logger
    ) {
    }

    public function build(
        BinaryDataReader $binaryDataReader,
        EventInfo $eventInfo,
        string $schemaName,
        string $tableName,
        string $tableId,
        int $columnsAmount,
        string $columnTypes
    ): TableMap {
        $rawColumnDTOs = $this->readStructuralColumnData($binaryDataReader, $columnTypes);

        // null-bitmap: must be consumed to reach the metadata fields that follow it
        $binaryDataReader->read($this->getColumnsBinarySize($columnsAmount));

        $tableMapMetadata = $this->parseMetadata($binaryDataReader, $eventInfo, $rawColumnDTOs);

        // binlog_row_metadata=MINIMAL still writes a metadata block (e.g. signedness) but
        // never column names; only FULL emits COLUMN_NAME for every column, so an empty
        // columnNames list means we don't actually have enough to skip information_schema.
        if ($tableMapMetadata !== null && $tableMapMetadata->columnNames !== []) {
            $this->logger->debug('Resolving column metadata for ' . $schemaName . '.' . $tableName . ' from TABLE_MAP_EVENT metadata');
            $fieldDTOCollection = $this->makeFieldDTOCollectionFromMetadata($rawColumnDTOs, $tableMapMetadata);
        } else {
            $this->logger->debug('Resolving column metadata for ' . $schemaName . '.' . $tableName . ' from information_schema');
            $fieldDTOCollection = $this->repository->getFields($schemaName, $tableName);
        }

        $columnDTOCollection = $this->makeColumnDTOCollection($rawColumnDTOs, $fieldDTOCollection);

        return new TableMap($schemaName, $tableName, $tableId, $columnsAmount, $columnDTOCollection);
    }

    /**
     * @return array<int, ColumnDTO>
     */
    private function readStructuralColumnData(BinaryDataReader $binaryDataReader, string $columnTypes): array
    {
        // structural pass: consumes the per-column metadata bytes and resolves the real
        // ENUM/SET type; the FieldDTO placeholder is discarded, replaced once we know
        // whether we're building fields from the binlog metadata or information_schema
        $rawColumnDTOs = [];
        $columnLength = strlen($columnTypes);
        for ($offset = 0; $offset < $columnLength; ++$offset) {
            $type = ord($columnTypes[$offset]);
            $rawColumnDTOs[$offset] = ColumnDTO::make($type, FieldDTO::makeDummy($offset), $binaryDataReader);
        }

        return $rawColumnDTOs;
    }

    /**
     * @param array<int, ColumnDTO> $rawColumnDTOs
     */
    private function parseMetadata(BinaryDataReader $binaryDataReader, EventInfo $eventInfo, array $rawColumnDTOs): ?TableMapMetadata
    {
        if (!$this->config->useTableMapMetadata) {
            return null;
        }

        $metadataLength = $binaryDataReader->getBinaryDataLength() - ($eventInfo->checkSum ? 4 : 0);
        if ($metadataLength <= 0) {
            $this->logger->debug('No metadata bytes present in TABLE_MAP_EVENT (metadataLength: ' . $metadataLength . ')');

            return null;
        }

        return TableMapMetadata::parse(
            $binaryDataReader,
            $metadataLength,
            array_map(static fn (ColumnDTO $columnDTO): int => $columnDTO->type, $rawColumnDTOs)
        );
    }

    /**
     * @param array<int, ColumnDTO> $rawColumnDTOs
     */
    private function makeFieldDTOCollectionFromMetadata(array $rawColumnDTOs, TableMapMetadata $tableMapMetadata): FieldDTOCollection
    {
        $fieldDTOCollection = new FieldDTOCollection();
        foreach ($rawColumnDTOs as $offset => $rawColumnDTO) {
            $fieldDTOCollection->set($offset, $this->makeFieldDTOFromMetadata($offset, $rawColumnDTO->type, $tableMapMetadata));
        }

        return $fieldDTOCollection;
    }

    private function makeFieldDTOFromMetadata(int $offset, int $resolvedType, TableMapMetadata $tableMapMetadata): FieldDTO
    {
        $columnType = match ($resolvedType) {
            ConstFieldType::ENUM => 'enum(' . implode(
                ',',
                array_map(static fn (string $value): string => "'" . $value . "'", $tableMapMetadata->enumValues[$offset] ?? [])
            ) . ')',
            ConstFieldType::SET => 'set(' . implode(
                ',',
                array_map(static fn (string $value): string => "'" . $value . "'", $tableMapMetadata->setValues[$offset] ?? [])
            ) . ')',
            default => $tableMapMetadata->isUnsigned($offset) ? 'unsigned' : '',
        };

        return new FieldDTO(
            $tableMapMetadata->getColumnName($offset, $offset),
            null,
            null,
            '',
            $columnType,
            $tableMapMetadata->isPrimaryKey($offset) ? 'PRI' : ''
        );
    }

    /**
     * @param array<int, ColumnDTO> $rawColumnDTOs
     */
    private function makeColumnDTOCollection(array $rawColumnDTOs, FieldDTOCollection $fieldDTOCollection): ColumnDTOCollection
    {
        $columnDTOCollection = new ColumnDTOCollection();

        // if you drop tables and parse of logs you will get empty scheme
        if ($fieldDTOCollection->isEmpty()) {
            return $columnDTOCollection;
        }

        foreach ($rawColumnDTOs as $offset => $rawColumnDTO) {
            // this a dirty hack to prevent row events containing columns which have been dropped
            // (only reachable via the information_schema fallback; the binlog metadata path is
            // self-describing and always covers every column written into this very event)
            if ($fieldDTOCollection->offsetExists($offset)) {
                $fieldDTO = $fieldDTOCollection->offsetGet($offset);
                $type = $rawColumnDTO->type;
            } else {
                $fieldDTO = FieldDTO::makeDummy($offset);
                $type = ConstFieldType::IGNORE;
            }

            if ($fieldDTO !== null) {
                $columnDTOCollection->set(
                    $offset,
                    new ColumnDTO(
                        $fieldDTO,
                        $type,
                        $rawColumnDTO->maxLength,
                        $rawColumnDTO->size,
                        $rawColumnDTO->fsp,
                        $rawColumnDTO->lengthSize,
                        $rawColumnDTO->precision,
                        $rawColumnDTO->decimals,
                        $rawColumnDTO->bits,
                        $rawColumnDTO->bytes
                    )
                );
            }
        }

        return $columnDTOCollection;
    }

    private function getColumnsBinarySize(int $columnsAmount): int
    {
        return (int)(($columnsAmount + 7) / 8);
    }
}
