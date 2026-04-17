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
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PageSystemConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class PageSystemConfigConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        private readonly SystemConfigLookup $systemConfigLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === PageSystemConfigDataSet::getEntity();
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
                        ->withEntityName(DefaultEntities::PAGE_SYSTEM_CONFIG)
                        ->withFieldName('salesChannelId')
                        ->withSourceData($data)
                        ->build(ConvertAssociationMissingLog::class)
                );

                return new ConvertStruct(null, $data);
            }
        }

        $systemConfigUuid = $this->systemConfigLookup->get($data['configurationKey'], $mappedSalesChannelId, $this->context);
        if ($systemConfigUuid !== null) {
            $converted['id'] = $systemConfigUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SYSTEM_CONFIG,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['salesChannelId'])) {
            $converted['salesChannelId'] = $mappedSalesChannelId;
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
