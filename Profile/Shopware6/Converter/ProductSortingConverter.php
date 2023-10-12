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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductSortingDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class ProductSortingConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === ProductSortingDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;
        [$productSortingUuid, $isLocked] = $this->mappingService->getProductSortingUuid(
            $data['key'],
            $this->context
        );

        if ($productSortingUuid !== null) {
            $converted['id'] = $productSortingUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT_SORTING,
            $data['id'],
            $converted['id']
        );

        if ($isLocked) {
            return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
        }

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::PRODUCT_SORTING
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
