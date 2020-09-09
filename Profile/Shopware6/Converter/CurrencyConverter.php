<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EntityAlreadyExistsRunLog;

abstract class CurrencyConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $oldCurrencyId = $data['id'];
        $newCurrencyId = $this->mappingService->getCurrencyUuidWithoutMapping(
            $this->connectionId,
            $data['isoCode'],
            $this->context
        );

        if ($newCurrencyId !== null) {
            // currency with that iso code already exists - no need to migrate this currency
            $this->loggingService->addLogEntry(new EntityAlreadyExistsRunLog(
                $this->runId,
                DefaultEntities::CURRENCY,
                $data['id']
            ));

            // the mapping is still needed for dependencies
            $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
                DefaultEntities::CURRENCY,
                $oldCurrencyId,
                $newCurrencyId
            );

            return new ConvertStruct(null, $data, $this->mainMapping['id']);
        }

        // currency iso code does not exists here - create a new currency with the same old id
        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CURRENCY,
            $oldCurrencyId,
            $oldCurrencyId
        );

        $converted = $data;

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::CURRENCY
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
