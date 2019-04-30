<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class PropertyGroupOptionConverter extends Shopware55Converter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var string
     */
    private $locale;

    public function __construct(
        MappingServiceInterface $mappingService,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::PROPERTY_GROUP_OPTION;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->context = $context;
        $this->locale = $data['_locale'];
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();

        $converted = [
            'id' => $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
                $context
            ),

            'group' => [
                'id' => $this->mappingService->createNewUuid(
                    $this->connectionId,
                    DefaultEntities::PROPERTY_GROUP,
                    hash('md5', strtolower($data['group']['name'])),
                    $context
                ),
            ],
        ];

        $this->createAndDeleteNecessaryMappings($data, $converted);

        if (isset($data['media'])) {
            $this->getMedia($converted, $data);
        }

        $this->getConfiguratorSettings($data, $converted);
        $this->getProperties($data, $converted);
        $this->getTranslation($data, $converted);

        return new ConvertStruct($converted, null);
    }

    private function getMedia(array &$converted, array $data): void
    {
        if (!isset($data['media']['id'])) {
            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::PROPERTY_MEDIA_NOT_CONVERTED,
                'Property-Group-Option-Media could not be converted',
                'Property-Group-Option-Media could not be converted.',
                [
                    'uuid' => $converted['id'],
                    'id' => $data['id'],
                ]
            );

            return;
        }

        $newMedia = [];
        $newMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $data['media']['id'],
            $this->context
        );

        if (!isset($data['media']['name'])) {
            $data['media']['name'] = $newMedia['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'uri' => $data['media']['uri'] ?? $data['media']['path'],
                'fileName' => $data['media']['name'],
                'fileSize' => (int) $data['media']['file_size'],
                'mediaId' => $newMedia['id'],
            ]
        );

        $this->getMediaTranslation($newMedia, $data);
        $this->convertValue($newMedia, 'name', $data['media'], 'name');
        $this->convertValue($newMedia, 'description', $data['media'], 'description');

        $converted['media'] = $newMedia;
    }

    // Todo: Check if this is necessary, because name and description is currently not translatable
    private function getMediaTranslation(array &$media, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'name', $data['media'], 'name');
        $this->convertValue($localeTranslation, 'description', $data['media'], 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $media['translations'][$languageUuid] = $localeTranslation;
    }

    private function getConfiguratorSettings(array &$data, array &$converted): void
    {
        $variantOptionsToProductContainer = $this->mappingService->getUuidList(
            $this->connectionId,
            'main_product_options',
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $this->context
        );

        foreach ($variantOptionsToProductContainer as $uuid) {
            $converted['productConfiguratorSettings'][] = [
                'id' => $this->mappingService->createNewUuid(
                    $this->connectionId,
                    DefaultEntities::PRODUCT_PROPERTY,
                    $data['id'] . '_' . $uuid,
                    $this->context
                ),

                'productId' => $uuid,
            ];
        }
    }

    private function getProperties(array $data, array &$converted): void
    {
        $propertyOptionsToProductContainer = $this->mappingService->getUuidList(
            $this->connectionId,
            'main_product_filter',
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $this->context
        );

        foreach ($propertyOptionsToProductContainer as $uuid) {
            $converted['productProperties'][] = [
                'id' => $uuid,
            ];
        }
    }

    private function createAndDeleteNecessaryMappings(array $data, array $converted): void
    {
        $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_' . $data['type'],
            $data['id'],
            $this->context,
            null,
            $converted['id']
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_' . $data['type'],
            $data['group']['id'],
            $this->context,
            null,
            $converted['group']['id']
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_' . $data['type'])),
            $this->context
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            hash('md5', strtolower($data['group']['name'] . '_' . $data['type'])),
            $this->context
        );

        if ($data['type'] === 'option') {
            $propertyOptionMapping = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_property')),
                $this->context
            );

            $propertyGroupMapping = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP,
                hash('md5', strtolower($data['group']['name'] . '_property')),
                $this->context
            );

            if ($propertyOptionMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyOptionMapping,
                    $this->connectionId,
                    $this->context
                );
            }

            if ($propertyGroupMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyGroupMapping,
                    $this->connectionId,
                    $this->context
                );
            }
        }
    }

    private function getTranslation(array &$data, array &$converted): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        $defaultLanguageUuid = $language->getId();

        $converted['translations'][$defaultLanguageUuid] = [];
        $this->convertValue($converted['translations'][$defaultLanguageUuid], 'name', $data, 'name', self::TYPE_STRING);
        $this->convertValue($converted['translations'][$defaultLanguageUuid], 'position', $data, 'position', self::TYPE_INTEGER);

        $converted['group']['translations'][$defaultLanguageUuid] = [];
        $this->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'name', $data['group'], 'name', self::TYPE_STRING);
        $this->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'description', $data['group'], 'description', self::TYPE_STRING);
    }
}
