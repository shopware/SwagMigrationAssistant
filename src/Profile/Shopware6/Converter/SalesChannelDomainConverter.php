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
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDomainDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SalesChannelDomainConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SalesChannelDomainDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SALES_CHANNEL_DOMAIN,
            $data['id'],
            $converted['id']
        );

        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $data['languageId']);
        $converted['currencyId'] = $this->getMappingIdFacade(DefaultEntities::CURRENCY, $data['currencyId']);
        $converted['snippetSetId'] = $this->getMappingIdFacade(DefaultEntities::SNIPPET_SET, $data['snippetSetId']);
        $converted['salesChannelId'] = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $data['salesChannelId']);

        if ($converted['salesChannelId'] === null) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(DefaultEntities::SALES_CHANNEL_DOMAIN)
                    ->withFieldName('salesChannelId')
                    ->withSourceData($data)
                    ->build(ConvertAssociationMissingLog::class)
            );

            return new ConvertStruct(null, $data);
        }

        if (isset($data['salesChannelDefaultHreflang'])) {
            $salesChannelDefaultHreflangId = $this->getMappingIdFacade(DefaultEntities::SALES_CHANNEL, $data['salesChannelDefaultHreflang']['id']);

            if ($salesChannelDefaultHreflangId !== null) {
                $converted['salesChannelDefaultHreflang'] = [
                    'id' => $salesChannelDefaultHreflangId,
                ];
            } else {
                unset($converted['salesChannelDefaultHreflang']);
            }
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
