<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;

interface MigrationContextInterface
{
    public function getRunUuid(): string;

    public function getConnection(): ?SwagMigrationConnectionEntity;

    public function getEntity(): string;

    public function getOffset(): int;

    public function getLimit(): int;
}
