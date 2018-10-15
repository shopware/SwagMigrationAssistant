<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;

interface MigrationEnvironmentServiceInterface
{
    /**
     * Returns the total number of entities which should be imported
     */
    public function getEntityTotal(MigrationContext $migrationContext): int;

    /**
     * Reads the complete environment information from the source system
     */
    public function getEnvironmentInformation(MigrationContext $migrationContext): EnvironmentInformation;
}
