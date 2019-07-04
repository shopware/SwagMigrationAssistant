<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class OrderAttributeConverter extends AttributeConverter
{
    public function getSupportedEntityName(): string
    {
        return OrderAttributeDataSet::getEntity();
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
        return DefaultEntities::ORDER;
    }
}
