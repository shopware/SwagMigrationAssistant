<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedMailTemplateType;

abstract class MailTemplateConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::MAIL_TEMPLATE,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::MAIL_TEMPLATE
        );

        if (isset($converted['mailTemplateType']['technicalName'])) {
            $typeUuid = $this->mappingService->getMailTemplateTypeUuid($converted['mailTemplateType']['technicalName'], $converted['mailTemplateTypeId'], $this->migrationContext, $this->context);

            if ($typeUuid === null) {
                $this->loggingService->addLogEntry(
                    new UnsupportedMailTemplateType(
                        $this->runId,
                        $data['id'],
                        $converted['mailTemplateType']['technicalName']
                    )
                );

                return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
            }

            unset($converted['mailTemplateType']);
            $converted['mailTemplateTypeId'] = $typeUuid;
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
