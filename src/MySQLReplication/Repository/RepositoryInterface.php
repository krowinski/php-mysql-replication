<?php

declare(strict_types=1);

namespace MySQLReplication\Repository;

interface RepositoryInterface
{
    public function getFields(string $database, string $table): FieldDTOCollection;

    public function isCheckSum(): bool;

    public function isRowFormat(): bool;

    public function isRowImageFull(): bool;

    public function getVersion(): string;

    public function getGtidExecuted(): string;

    public function isSemiSyncEnabled(): bool;

    public function getMasterStatus(): MasterStatusDTO;
}
