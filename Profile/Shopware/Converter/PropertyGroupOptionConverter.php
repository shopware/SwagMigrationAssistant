<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertChildEntity;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;

abstract class PropertyGroupOptionConverter extends ShopwareConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    private $checksum;

    public function __construct(
        MappingServiceInterface $mappingService,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSourceIdentifier(array $data): string
    {
        return hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_' . $data['type']));
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->checksum = $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();

        if (!isset($data['group']['name'])) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                $data['id'],
                'group'
            ));

            return new ConvertStruct(null, $data);
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $context
        );
        $this->mappingIds[] = $mapping['id'];

        $propertyGroupMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP,
            hash('md5', strtolower($data['group']['name'])),
            $context
        );
        $this->mappingIds[] = $propertyGroupMapping['id'];

        $converted = [
            'id' => $this->mapping['entityUuid'],
            'group' => [
                'id' => $propertyGroupMapping['entityUuid'],
            ],
        ];

        $this->createAndDeleteNecessaryMappings($data, $converted);

        if (isset($data['media'])) {
            $this->getMedia($converted, $data);
        }

        $this->getConfiguratorSettings($data, $converted);
        $this->getProperties($data, $converted);
        $this->getTranslation($data, $converted);

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $context
        );

        return new ConvertStruct($converted, null, $this->mapping['id']);
    }

    protected function getMedia(array &$converted, array $data): void
    {
        if (!isset($data['media']['id'])) {
            $this->loggingService->addLogEntry(new CannotConvertChildEntity(
                $this->runId,
                'property_group_option_media',
                DefaultEntities::PROPERTY_GROUP_OPTION,
                $data['id']
            ));

            return;
        }

        $newMedia = [];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $data['media']['id'],
            $this->context
        );
        $newMedia['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        if (!isset($data['media']['name'])) {
            $data['media']['name'] = $newMedia['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => MediaDataSet::getEntity(),
                'uri' => $data['media']['uri'] ?? $data['media']['path'],
                'fileName' => $data['media']['name'],
                'fileSize' => (int) $data['media']['file_size'],
                'mediaId' => $newMedia['id'],
            ]
        );

        $this->getMediaTranslation($newMedia, $data);
        $this->convertValue($newMedia, 'name', $data['media'], 'name');
        $this->convertValue($newMedia, 'description', $data['media'], 'description');

        $albumMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $data['media']['albumID'],
            $this->context
        );

        if ($albumMapping !== null) {
            $newMedia['mediaFolderId'] = $albumMapping['entityUuid'];
            $this->mappingIds[] = $albumMapping['id'];
        }

        $converted['media'] = $newMedia;
    }

    // Todo: Check if this is necessary, because name and description is currently not translatable
    protected function getMediaTranslation(array &$media, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'name', $data['media'], 'name');
        $this->convertValue($localeTranslation, 'description', $data['media'], 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $media['translations'][$languageUuid] = $localeTranslation;
    }

    protected function getConfiguratorSettings(array &$data, array &$converted): void
    {
        $variantOptionsToProductContainer = $this->mappingService->getUuidList(
            $this->connectionId,
            'main_product_options',
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $this->context
        );

        foreach ($variantOptionsToProductContainer as $uuid) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_PROPERTY,
                $data['id'] . '_' . $uuid,
                $this->context
            );
            $this->mappingIds[] = $mapping['id'];

            $converted['productConfiguratorSettings'][] = [
                'id' => $mapping['entityUuid'],
                'productId' => $uuid,
            ];
        }
    }

    protected function getProperties(array $data, array &$converted): void
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

    protected function createAndDeleteNecessaryMappings(array $data, array $converted): void
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_' . $data['type'],
            $data['id'],
            $this->context,
            null,
            null,
            $converted['id']
        );
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_' . $data['type'],
            $data['group']['id'],
            $this->context,
            null,
            null,
            $converted['group']['id']
        );
        $this->mappingIds[] = $mapping['id'];

        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_' . $data['type'])),
            $this->context,
            $this->checksum
        );

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            hash('md5', strtolower($data['group']['name'] . '_' . $data['type'])),
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        if ($data['type'] === 'option') {
            $propertyOptionMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_property')),
                $this->context
            );

            $propertyGroupMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP,
                hash('md5', strtolower($data['group']['name'] . '_property')),
                $this->context
            );

            if ($propertyOptionMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyOptionMapping['entityUuid'],
                    $this->connectionId,
                    $this->context
                );
            }

            if ($propertyGroupMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyGroupMapping['entityUuid'],
                    $this->connectionId,
                    $this->context
                );
            }
        }
    }

    protected function getTranslation(array &$data, array &$converted): void
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
