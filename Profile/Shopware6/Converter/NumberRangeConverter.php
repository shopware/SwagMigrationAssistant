<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedNumberRangeTypeLog;

abstract class NumberRangeConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::NUMBER_RANGE
        );

        if (isset($converted['type']['technicalName'])) {
            $typeUuid = $this->mappingService->getNumberRangeTypeUuid($converted['type']['technicalName'], $converted['typeId'], $this->migrationContext, $this->context);

            if ($typeUuid === null) {
                $this->loggingService->addLogEntry(
                    new UnsupportedNumberRangeTypeLog(
                        $this->runId,
                        DefaultEntities::NUMBER_RANGE,
                        $data['id'],
                        $converted['typeId']
                    )
                );

                return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
            }

            unset($converted['type']);
            $converted['typeId'] = $typeUuid;
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
