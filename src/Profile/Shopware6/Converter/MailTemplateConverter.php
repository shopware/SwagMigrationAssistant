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
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MailTemplateTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemDefaultMailTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedMailTemplateType;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class MailTemplateConverter extends ShopwareMediaConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService,
        protected readonly MailTemplateTypeLookup $mailTemplateTypeLookup,
        protected readonly SystemDefaultMailTemplateLookup $systemDefaultMailTemplateLookup,
    ) {
        parent::__construct($mappingService, $loggingService, $mediaFileService);
    }

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
            $mailTemplateTypeMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::MAIL_TEMPLATE_TYPE, $data['mailTemplateTypeId'], $this->context);
            if ($mailTemplateTypeMapping !== null) {
                $typeUuid = $mailTemplateTypeMapping['entityUuid'];
            } else {
                $typeUuid = $this->mailTemplateTypeLookup->get($converted['mailTemplateType']['technicalName'], $this->context);
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

                $this->mappingService->createMapping(
                    $this->connectionId,
                    DefaultEntities::MAIL_TEMPLATE_TYPE,
                    $data['mailTemplateTypeId'],
                    $this->checksum,
                    null,
                    $typeUuid,
                );
            }

            unset($converted['mailTemplateType']);
            $converted['mailTemplateTypeId'] = $typeUuid;
        }

        if ($data['systemDefault']) {
            $defaultMailTemplateMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::MAIL_TEMPLATE, $data['id'], $this->context);
            if ($defaultMailTemplateMapping !== null) {
                $defaultMailTemplateUuid = $defaultMailTemplateMapping['entityUuid'];
            } else {
                $defaultMailTemplateUuid = $this->systemDefaultMailTemplateLookup->get($converted['mailTemplateTypeId'], $this->context);
            }

            $converted['id'] = $defaultMailTemplateUuid;
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
