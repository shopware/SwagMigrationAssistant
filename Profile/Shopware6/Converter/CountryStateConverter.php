<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryStateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class CountryStateConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CountryStateDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $countryStateUuid = $this->mappingService->getCountryStateUuid($data['id'], $data['country']['iso'], $data['country']['iso3'], $data['shortCode'], $this->connectionId, $this->context);
        unset($converted['country']);

        if ($countryStateUuid !== null) {
            $converted['id'] = $countryStateUuid;
        }

        $converted['countryId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $data['countryId']);

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::COUNTRY_STATE,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::COUNTRY_STATE
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
