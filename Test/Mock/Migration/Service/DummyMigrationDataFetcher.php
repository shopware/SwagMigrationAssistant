<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Service;

use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationDataFetcher;

class DummyMigrationDataFetcher extends MigrationDataFetcher
{
    public function getEnvironmentInformation(MigrationContext $migrationContext): EnvironmentInformation
    {
        $environmentInformation = parent::getEnvironmentInformation($migrationContext);

        return new EnvironmentInformation(
            $environmentInformation->getSourceSystemName(),
            $environmentInformation->getSourceSystemVersion(),
            $environmentInformation->getSourceSystemDomain()
        );
    }
}
