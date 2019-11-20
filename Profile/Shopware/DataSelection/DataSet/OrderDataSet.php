<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class OrderDataSet extends ShopwareDataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::ORDER;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationOrders';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
