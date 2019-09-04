<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware56\Converter;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;

class Shopware56ShippingMethodConverter extends ShippingMethodConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware56Profile::PROFILE_NAME
            && $migrationContext->getDataSet()::getEntity() === ShippingMethodDataSet::getEntity();
    }
}
