<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class SeoUrlConverter extends ShopwareConverter
{
    /**
     * @var string
     */
    protected const PRODUCT_ROUTE_NAME = 'frontend.detail.page';

    /**
     * @var string
     */
    protected const CATEGORY_ROUTE_NAME = 'frontend.navigation.page';

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        if (isset($converted['foreignKey'])) {
            if ($converted['routeName'] === self::CATEGORY_ROUTE_NAME) {
                $relatedEntity = DefaultEntities::CATEGORY;
            } elseif ($converted['routeName'] === self::PRODUCT_ROUTE_NAME) {
                $relatedEntity = DefaultEntities::PRODUCT;
            } else {
                return new ConvertStruct(null, $converted);
            }

            $converted['foreignKey'] = $this->getMappingIdFacade(
                $relatedEntity,
                $converted['foreignKey']
            );
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
