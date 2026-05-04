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
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SeoUrlTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SeoUrlTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SeoUrlTemplateConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected readonly SeoUrlTemplateLookup $seoUrlTemplateLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SeoUrlTemplateDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $mappedSalesChannelId = null;
        if (isset($data['salesChannelId'])) {
            $mappedSalesChannelId = $this->getMappingIdFacade(
                DefaultEntities::SALES_CHANNEL,
                $data['salesChannelId']
            );

            if ($mappedSalesChannelId === null) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                        ->withEntityName(DefaultEntities::SEO_URL_TEMPLATE)
                        ->withFieldName('salesChannelId')
                        ->withSourceData($data)
                        ->build(ConvertAssociationMissingLog::class)
                );

                return new ConvertStruct(null, $data);
            }
        }

        $seoUrlTemplateUuid = $this->seoUrlTemplateLookup->get(
            $mappedSalesChannelId,
            $data['routeName'],
            $this->context
        );

        if ($seoUrlTemplateUuid !== null) {
            $converted['id'] = $seoUrlTemplateUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SEO_URL_TEMPLATE,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['salesChannelId'])) {
            $converted['salesChannelId'] = $mappedSalesChannelId;
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
