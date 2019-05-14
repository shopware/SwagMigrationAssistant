<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Service;

use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;

class DummyMigrationDataFetcher extends MigrationDataFetcher
{
    public function getEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation
    {
        $environmentInformation = parent::getEnvironmentInformation($migrationContext);

        return new EnvironmentInformation(
            $environmentInformation->getSourceSystemName(),
            $environmentInformation->getSourceSystemVersion(),
            $environmentInformation->getSourceSystemDomain()
        );
    }
}
