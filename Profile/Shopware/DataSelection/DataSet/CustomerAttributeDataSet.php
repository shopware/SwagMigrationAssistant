<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class CustomerAttributeDataSet extends ShopwareDataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::CUSTOMER_CUSTOM_FIELD;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationAttributes';
    }

    public function getExtraQueryParameters(): array
    {
        return [
            'attribute_table' => 's_user_attributes',
        ];
    }
}
