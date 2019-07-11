<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55CustomerAttributeConverter extends CustomerAttributeConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware55Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === CustomerAttributeDataSet::getEntity();
    }
}
