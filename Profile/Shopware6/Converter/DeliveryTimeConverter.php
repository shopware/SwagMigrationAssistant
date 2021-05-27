<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class DeliveryTimeConverter extends ShopwareConverter
{
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $converted['id'] = $this->mappingService->getDeliveryTime($this->connectionId, $this->context, $data['min'], $data['max'], $data['unit'], $data['id']);

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::DELIVERY_TIME,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['translations'])) {
            $this->updateAssociationIds(
                $converted['translations'],
                DefaultEntities::LANGUAGE,
                'languageId',
                DefaultEntities::DELIVERY_TIME
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
