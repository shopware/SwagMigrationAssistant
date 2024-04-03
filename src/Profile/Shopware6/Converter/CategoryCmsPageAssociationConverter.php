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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryCmsPageAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class CategoryCmsPageAssociationConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CategoryCmsPageAssociationDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;
        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CATEGORY,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['cmsPageId'])) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CMS_PAGE,
                $converted['cmsPageId'],
                $this->context
            );

            if ($mapping === null) {
                return new ConvertStruct(null, $converted);
            }
            $converted['cmsPageId'] = $mapping['entityUuid'];
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
