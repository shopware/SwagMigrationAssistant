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
use SwagMigrationAssistant\Migration\Logging\Log\ConvertObjectTypeUnsupportedLog;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SeoUrlConverter extends ShopwareConverter
{
    /**
     * @var string
     */
    protected const PRODUCT_ROUTE_NAME = 'frontend.detail.page';

    /**
     * @var string
     */
    protected const CATEGORY_ROUTE_NAME = 'frontend.navigation.page';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SeoUrlDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        if (isset($data['isModified']) && $data['isModified'] === false) {
            return new ConvertStruct(null, $data);
        }

        $converted = $data;

        if (isset($converted['foreignKey'])) {
            if (!isset($converted['routeName'])) {
                $converted['foreignKey'] = null;
            } elseif ($converted['routeName'] === self::CATEGORY_ROUTE_NAME) {
                $converted['foreignKey'] = $this->getMappingIdFacade(
                    DefaultEntities::CATEGORY,
                    $converted['foreignKey']
                );
            } elseif ($converted['routeName'] === self::PRODUCT_ROUTE_NAME) {
                $converted['foreignKey'] = $this->getMappingIdFacade(
                    DefaultEntities::PRODUCT,
                    $converted['foreignKey']
                );
            } else {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                        ->withEntityName(DefaultEntities::SEO_URL)
                        ->withFieldName('routeName')
                        ->withSourceData($data)
                        ->build(ConvertObjectTypeUnsupportedLog::class)
                );

                return new ConvertStruct(null, $data);
            }
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SEO_URL,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['salesChannelId'])) {
            $converted['salesChannelId'] = $this->getMappingIdFacade(
                DefaultEntities::SALES_CHANNEL,
                $converted['salesChannelId']
            );
        }

        if (isset($converted['languageId'])) {
            $converted['languageId'] = $this->getMappingIdFacade(
                DefaultEntities::LANGUAGE,
                $converted['languageId']
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
