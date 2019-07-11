<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class DataSet
{
    abstract public static function getEntity(): string;

    abstract public function supports(MigrationContextInterface $migrationContext): bool;

    public function getCountingInformation(): ?CountingInformationStruct
    {
        return null;
    }
}
