<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55CategoryConverter extends CategoryConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware55Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === CategoryDataSet::getEntity();
    }
}
