<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingInformationStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class PropertyGroupOptionDataSet extends ShopwareDataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::PROPERTY_GROUP_OPTION;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getCountingInformation(): ?CountingInformationStruct
    {
        $information = new CountingInformationStruct(self::getEntity());
        $information->addQueryStruct(new CountingQueryStruct('s_article_configurator_options'));
        $information->addQueryStruct(new CountingQueryStruct('s_filter_values'));

        return $information;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationConfiguratorOptions';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
