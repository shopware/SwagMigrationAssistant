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

abstract class LanguageConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convertData(array $data): ConvertStruct
    {
        $oldLanguageId = $data['id'];
        $newLanguageId = $this->mappingService->getLanguageUuid(
            $this->connectionId,
            $data['locale']['code'],
            $this->context,
            true
        );

        if ($newLanguageId !== null) {
            // language with that iso code already exists - no need to migrate this language
            $this->loggingService->addLogEntry(new EntityAlreadyExistsRunLog(
                $this->runId,
                DefaultEntities::LANGUAGE,
                $data['id']
            ));

            // the mapping is still needed for dependencies
            $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
                DefaultEntities::LANGUAGE,
                $oldLanguageId,
                $newLanguageId
            );

            return new ConvertStruct(null, $data, $this->mainMapping['id']);
        }

        // language iso code does not exists here - create a new language with the same id
        $newLanguageId = $oldLanguageId;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::LANGUAGE,
            $oldLanguageId,
            $newLanguageId
        );

        $converted = [];
        $converted['id'] = $data['id'];
        $converted['name'] = $data['name'];

        $localeUuid = $this->mappingService->getLocaleUuid(
            $this->connectionId,
            $data['locale']['code'],
            $this->context
        );
        $converted['localeId'] = $localeUuid;
        $converted['translationCodeId'] = $localeUuid;

        unset(
            $data['id'],
            $data['name'],
            $data['localeId'],
            $data['translationCodeId']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id']);
    }
}
