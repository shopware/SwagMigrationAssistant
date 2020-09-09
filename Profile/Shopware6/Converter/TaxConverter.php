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

abstract class TaxConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $oldTaxId = $data['id'];
        $newTaxId = $this->mappingService->getTaxUuid(
            $this->connectionId,
            $data['taxRate'],
            $this->context
        );

        if ($newTaxId !== null) {
            // tax with that rate already exists - no need to migrate this tax
            $this->loggingService->addLogEntry(new EntityAlreadyExistsRunLog(
                $this->runId,
                DefaultEntities::TAX,
                $data['id']
            ));

            // the mapping is still needed for dependencies
            $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
                DefaultEntities::TAX,
                $oldTaxId,
                $newTaxId
            );

            return new ConvertStruct(null, $data, $this->mainMapping['id']);
        }

        // tax rate does not exists here - create a new tax with the same id
        $newTaxId = $oldTaxId;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::TAX,
            $oldTaxId,
            $newTaxId
        );

        $converted = $data;

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
