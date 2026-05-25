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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelHomeCmsPageAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SalesChannelHomeCmsPageAssociationConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SalesChannelHomeCmsPageAssociationDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SALES_CHANNEL,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['homeCmsPageId'])) {
            $homeCmsPageId = $this->getMappingIdFacade(
                DefaultEntities::CMS_PAGE,
                $converted['homeCmsPageId']
            );

            if ($homeCmsPageId === null) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                        ->withEntityName(DefaultEntities::SALES_CHANNEL)
                        ->withConvertedData($converted)
                        ->build(ConvertAssociationMissingLog::class)
                );

                unset($converted['homeCmsPageId']);
            } else {
                $converted['homeCmsPageId'] = $homeCmsPageId;
            }
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
