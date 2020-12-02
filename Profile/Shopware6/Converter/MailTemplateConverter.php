<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedMailTemplateType;
use SwagMigrationAssistant\Profile\Shopware6\Mapping\Shopware6MappingServiceInterface;

abstract class MailTemplateConverter extends ShopwareConverter
{
    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    public function __construct(
        Shopware6MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);
        $this->mediaFileService = $mediaFileService;
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
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

        if (isset($converted['media'])) {
            foreach ($converted['media'] as &$mediaAssociation) {
                $this->updateMediaAssociation($mediaAssociation['media']);
            }
            unset($mediaAssociation);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
