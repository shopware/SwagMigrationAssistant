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
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductCanonicalAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class ProductCanonicalAssociationConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === ProductCanonicalAssociationDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['canonicalProductId'])) {
            $canonicalProductId = $this->getMappingIdFacade(
                DefaultEntities::PRODUCT,
                $converted['canonicalProductId']
            );

            if ($canonicalProductId === null) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                        ->withEntityName(DefaultEntities::PRODUCT)
                        ->withConvertedData($converted)
                        ->build(ConvertAssociationMissingLog::class)
                );

                unset($converted['canonicalProductId']);
            } else {
                $converted['canonicalProductId'] = $canonicalProductId;
            }
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
