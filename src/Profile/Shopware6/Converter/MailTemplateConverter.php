<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedMailTemplateType;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('services-settings')]
class MailTemplateConverter extends ShopwareMediaConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === MailTemplateDataSet::getEntity();
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaIds = [];
        foreach ($converted as $template) {
            if (isset($template['media'])) {
                foreach ($template['media'] as $media) {
                    $mediaIds[] = $media['media']['id'];
                }
            }
        }

        return $mediaIds;
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

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

                return new ConvertStruct(null, $data, $converted['id'] ?? null);
            }

            unset($converted['mailTemplateType']);
            $converted['mailTemplateTypeId'] = $typeUuid;
        }

        if ($data['systemDefault']) {
            $converted['id'] = $this->mappingService->getSystemDefaultMailTemplateUuid($converted['mailTemplateTypeId'], $data['id'], $this->connectionId, $this->migrationContext, $this->context);
        }

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

        if (isset($converted['media'])) {
            foreach ($converted['media'] as &$mediaAssociation) {
                $this->updateMediaAssociation($mediaAssociation['media']);
            }
            unset($mediaAssociation);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
