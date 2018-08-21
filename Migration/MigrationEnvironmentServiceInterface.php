<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

interface MigrationEnvironmentServiceInterface
{
    /**
     * Returns the total number of entities which should be imported
     */
    public function getEntityTotal(MigrationContext $migrationContext): int;

    /**
     * Reads the complete environment information from the source system
     */
    public function getEnvironmentInformation(MigrationContext $migrationContext): array;
}
