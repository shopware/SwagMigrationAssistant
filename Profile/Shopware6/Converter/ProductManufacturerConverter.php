<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;

abstract class ProductManufacturerConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    public function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT_MANUFACTURER,
            $data['id'],
            $converted['id']
        );

        foreach ($converted['translations'] as $key => $translation) {
            $oldLanguageId = $translation['languageId'];

            $newLanguageId = $this->getMappingIdFacade(
                DefaultEntities::LANGUAGE,
                $oldLanguageId
            );

            if (empty($newLanguageId)) {
                $this->loggingService->addLogEntry(new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::LANGUAGE,
                    $oldLanguageId,
                    DefaultEntities::PRODUCT_MANUFACTURER
                ));

                continue;
            }

            $converted['translations'][$key]['languageId'] = $newLanguageId;
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id']);
    }
}
