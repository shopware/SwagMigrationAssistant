<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware54\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

class Shopware54ProductPriceAttributeConverter extends ProductPriceAttributeConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware54Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === ProductPriceAttributeDataSet::getEntity();
    }
}
