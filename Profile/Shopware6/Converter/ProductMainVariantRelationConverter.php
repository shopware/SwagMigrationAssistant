<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class ProductMainVariantRelationConverter extends ShopwareConverter
{
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $productMapping = $this->getMappingIdFacade(DefaultEntities::PRODUCT, $data['id']);
        if ($productMapping === null) {
            return new ConvertStruct(null, $data);
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::MAIN_VARIANT_RELATION,
            $data['id'],
            $converted['id']
        );

        $variantMapping = $this->getMappingIdFacade(DefaultEntities::PRODUCT, $data['mainVariantId']);
        if ($variantMapping === null) {
            return new ConvertStruct(null, $data);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
