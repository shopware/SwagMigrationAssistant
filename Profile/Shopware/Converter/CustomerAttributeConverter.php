<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

abstract class CustomerAttributeConverter extends AttributeConverter
{
    public function getSupportedEntityName(): string
    {
        return CustomerAttributeDataSet::getEntity();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    protected function getCustomFieldEntityName(): string
    {
        return DefaultEntities::CUSTOMER;
    }
}
