<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class MediaDataSet extends ShopwareDataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::MEDIA;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationAssets';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }

    public function getMediaUuids(array $converted): ?array
    {
        return array_column($converted, 'id');
    }
}
