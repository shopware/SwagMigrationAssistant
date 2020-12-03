<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class ProductConverter extends ShopwareMediaConverter
{
    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $product) {
            if (isset($product['media'])) {
                foreach ($product['media'] as $media) {
                    $mediaIds[] = $media['media']['id'];
                }
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['price'])) {
            $this->updateAssociationIds(
                $converted['price'],
                DefaultEntities::CURRENCY,
                'currencyId',
                DefaultEntities::PRODUCT
            );
        }

        if (isset($converted['translations'])) {
            $this->updateAssociationIds(
                $converted['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::PRODUCT
            );
        }

        if (isset($converted['propertyIds'])) {
            $this->reformatMtoNAssociation(
                $converted,
                'propertyIds',
                'properties'
            );
        }

        if (isset($converted['optionIds'])) {
            $this->reformatMtoNAssociation(
                $converted,
                'optionIds',
                'options'
            );
        }

        if (isset($converted['taxId'])) {
            $converted['taxId'] = $this->getMappingIdFacade(
                DefaultEntities::TAX,
                $converted['taxId']
            );
        }

        if (isset($converted['manufacturerId'])) {
            $converted['manufacturerId'] = $this->getMappingIdFacade(
                DefaultEntities::PRODUCT_MANUFACTURER,
                $converted['manufacturerId']
            );
        }

        if (isset($converted['unitId'])) {
            $converted['unitId'] = $this->getMappingIdFacade(
                DefaultEntities::UNIT,
                $converted['unitId']
            );
        }

        if (isset($converted['purchasePrices'])) {
            $this->updateAssociationIds(
                $converted['purchasePrices'],
                DefaultEntities::CURRENCY,
                'currencyId',
                DefaultEntities::PRODUCT
            );
        }

        if (isset($converted['prices'])) {
            foreach ($converted['prices'] as &$price) {
                $this->updateAssociationIds(
                    $price['price'],
                    DefaultEntities::CURRENCY,
                    'currencyId',
                    DefaultEntities::PRODUCT
                );
            }
            unset($price);
        }

        if (isset($converted['deliveryTimeId'])) {
            $converted['deliveryTimeId'] = $this->getMappingIdFacade(
                DefaultEntities::DELIVERY_TIME,
                $converted['deliveryTimeId']
            );
        }

        if (isset($converted['media'])) {
            foreach ($converted['media'] as &$mediaAssociation) {
                $this->updateMediaAssociation($mediaAssociation['media']);
            }
            unset($mediaAssociation);
        }

        if (isset($converted['visibilities'])) {
            $this->updateAssociationIds(
                $converted['visibilities'],
                DefaultEntities::SALES_CHANNEL,
                'salesChannelId',
                DefaultEntities::PRODUCT,
                false
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
