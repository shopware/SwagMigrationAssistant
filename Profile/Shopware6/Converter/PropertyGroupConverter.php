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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PropertyGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class PropertyGroupConverter extends ShopwareMediaConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === PropertyGroupDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $group) {
            if (!isset($group['options'])) {
                continue;
            }

            foreach ($group['options'] as $option) {
                if (isset($option['media']['id'])) {
                    $mediaIds[] = $option['media']['id'];
                }
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PROPERTY_GROUP,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::PROPERTY_GROUP
        );

        foreach (\array_keys($converted['options']) as $key) {
            $this->convertOption($converted['options'][$key]);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    protected function convertOption(array &$option): void
    {
        $this->updateAssociationIds(
            $option['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::PROPERTY_GROUP
        );

        if (isset($option['media'])) {
            $this->updateMediaAssociation($option['media']);
        }
    }
}
