<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class OrderDocumentDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::ORDER_DOCUMENT;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaUuids = [];
        foreach ($converted as $data) {
            if (!isset($data['documentMediaFile']['id'])) {
                continue;
            }

            $mediaUuids[] = $data['documentMediaFile']['id'];
        }

        return $mediaUuids;
    }
}
