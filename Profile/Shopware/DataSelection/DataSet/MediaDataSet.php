<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class MediaDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::MEDIA;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }
}
