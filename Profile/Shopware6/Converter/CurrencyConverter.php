<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class CurrencyConverter extends ShopwareConverter
{
    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $currencyId = $this->mappingService->getCurrencyUuidWithoutMapping(
            $this->connectionId,
            $data['isoCode'],
            $this->context
        );

        if ($currencyId !== null) {
            $converted['id'] = $currencyId;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CURRENCY,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::CURRENCY
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
