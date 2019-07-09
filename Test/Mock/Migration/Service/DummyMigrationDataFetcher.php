<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;

class DummyMigrationDataFetcher extends MigrationDataFetcher
{
    public function getEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $environmentInformation = parent::getEnvironmentInformation($migrationContext, $context);

        return new EnvironmentInformation(
            $environmentInformation->getSourceSystemName(),
            $environmentInformation->getSourceSystemVersion(),
            $environmentInformation->getSourceSystemDomain()
        );
    }
}
