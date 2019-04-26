<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class MediaConverter extends Shopware55Converter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

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
        MediaFileServiceInterface $mediaFileService
    ) {
        $this->mappingService = $mappingService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::MEDIA;
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
            DefaultEntities::MEDIA,
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
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'description', $data, 'description');

        $albumUuid = $this->mappingService->getUuid(
          $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
          $data['albumID'],
          $this->context
        );

        if ($albumUuid !== null) {
            $converted['mediaFolderId'] = $albumUuid;
        }

        unset(
            $data['id'],
            $data['albumID'],

            // Legacy data which don't need a mapping or there is no equivalent field
            $data['path'],
            $data['type'],
            $data['extension'],
            $data['file_size'],
            $data['width'],
            $data['height'],
            $data['userID'],
            $data['created']
        );

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    // Todo: Check if this is necessary, because name and description is currently not translatable
    private function getMediaTranslation(array &$media, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($media, 'name', $data, 'name');
        $this->convertValue($media, 'description', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $media['translations'][$languageUuid] = $localeTranslation;
    }
}
