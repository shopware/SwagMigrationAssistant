<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MediaConverter extends AbstractConverter
{
    /**
     * @var MappingServiceInterface
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

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $connectionId;

    public function __construct(
        MappingServiceInterface $mappingService,
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
        $this->context = $context;
        $this->locale = $data['_locale'];
        unset($data['_locale']);
        $this->connectionId = $migrationContext->getConnection()->getId();

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
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

        $this->getMediaTranslation($converted, $data);
        $this->helper->convertValue($converted, 'name', $data, 'name');
        $this->helper->convertValue($converted, 'description', $data, 'description');

        unset(
            $data['id'],

            // Legacy data which don't need a mapping or there is no equivalent field
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

    // Todo: Check if this is necessary, because name and description is currently not translatable
    private function getMediaTranslation(array &$media, array $data): void
    {
        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        if ($languageData['createData']['localeCode'] === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->helper->convertValue($media, 'name', $data, 'name');
        $this->helper->convertValue($media, 'description', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            MediaTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $localeTranslation['language']['id'] = $languageData['uuid'];
            $localeTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $localeTranslation['languageId'] = $languageData['uuid'];
        }

        $media['translations'][$languageData['uuid']] = $localeTranslation;
    }
}
