<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MediaConverter extends AbstractConverter
{
    /**
     * @var Shopware55MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    public function __construct(
        Shopware55MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        MediaFileServiceInterface $mediaFileService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSupportedEntityName(): string
    {
        return MediaDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $locale = $data['_locale'];
        unset($data['_locale']);
        $connectionId = $migrationContext->getConnection()->getId();

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $connectionId,
            MediaDefinition::getEntityName(),
            $data['id'],
            $context
        );

        if (!isset($data['name'])) {
            $data['name'] = $converted['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $migrationContext->getRunUuid(),
                'uri' => $data['uri'] ?? $data['path'],
                'fileName' => $data['name'],
                'fileSize' => (int) $data['file_size'],
                'mediaId' => $converted['id'],
            ]
        );
        unset($data['uri'], $data['file_size']);

        $translation['id'] = $this->mappingService->createNewUuid(
            $connectionId,
            MediaTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $locale,
            $context
        );
        unset($data['id']);

        $this->helper->convertValue($translation, 'name', $data, 'name');
        $this->helper->convertValue($translation, 'description', $data, 'description');

        $languageData = $this->mappingService->getLanguageUuid($connectionId, $locale, $context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $translation;

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['path'],
            $data['type'],
            $data['extension'],
            $data['file_size'],
            $data['width'],
            $data['height'],
            $data['userID'],
            $data['created'],
            $data['album'],
            $data['albumID']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }
}
