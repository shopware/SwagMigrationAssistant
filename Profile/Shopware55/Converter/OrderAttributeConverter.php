<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

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

    protected function getAttributeEntityName(): string
    {
        return DefaultEntities::ORDER;
    }
}
