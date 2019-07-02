<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingInformationStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class SalesChannelDataSet extends Shopware55DataSet
{
    public static function getEntity(): string
    {
        return DefaultEntities::SALES_CHANNEL;
    }

    public function supports(string $profileName): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getCountingInformation(): ?CountingInformationStruct
    {
        $information = new CountingInformationStruct(self::getEntity());
        $information->addQueryStruct(new CountingQueryStruct('s_core_shops'));

        return $information;
    }

    public function getApiRoute(): string
    {
        return 'SwagMigrationShops';
    }

    public function getExtraQueryParameters(): array
    {
        return [];
    }
}
