<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class ShippingMethodConverter extends ShopwareMediaConverter
{
    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $manufacturer) {
            if (isset($manufacturer['media']['id'])) {
                $mediaIds[] = $manufacturer['media']['id'];
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SHIPPING_METHOD,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::NUMBER_RANGE
        );

        if (isset($converted['prices'])) {
            foreach ($converted['prices'] as &$price) {
                $this->updateAssociationIds(
                    $price['currencyPrice'],
                    DefaultEntities::CURRENCY,
                    'currencyId',
                    DefaultEntities::PRODUCT
                );
            }
            unset($price);
        }

        if (isset($data['taxId'])) {
            $converted['taxId'] = $this->getMappingIdFacade(DefaultEntities::TAX, $data['taxId']);
        }

        if (isset($data['deliveryTimeId'])) {
            $converted['deliveryTimeId'] = $this->getMappingIdFacade(DefaultEntities::DELIVERY_TIME, $data['deliveryTimeId']);
        }

        if (isset($converted['media'])) {
            $this->updateMediaAssociation($converted['media']);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
