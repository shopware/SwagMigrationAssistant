<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class DataSet
{
    abstract public static function getEntity(): string;

    abstract public function supports(MigrationContextInterface $migrationContext): bool;

    public function getCountingInformation(?MigrationContextInterface $migrationContext = null): ?CountingInformationStruct
    {
        return null;
    }

    public function getMediaUuids(array $converted): ?array
    {
        return null;
    }
}
