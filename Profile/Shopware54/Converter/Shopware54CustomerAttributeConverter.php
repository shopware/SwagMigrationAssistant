<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware54\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

class Shopware54CustomerAttributeConverter extends CustomerAttributeConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware54Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === CustomerAttributeDataSet::getEntity();
    }
}
