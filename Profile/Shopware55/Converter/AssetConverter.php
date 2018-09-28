<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Asset\MediaFileServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class AssetConverter implements ConverterInterface
{
    /**
     * @var Shopware55MappingService
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
        Shopware55MappingService $mappingService,
        ConverterHelperService $converterHelperService,
        MediaFileServiceInterface $mediaFileService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->mediaFileService = $mediaFileService;
    }

    public function supports(): string
    {
        return MediaDefinition::getEntityName();
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(
        array $data,
        Context $context,
        string $runId,
        string $profileId,
        string $runId,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ): ConvertStruct {
        $locale = $data['_locale'];
        unset($data['_locale']);

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $profileId,
            MediaDefinition::getEntityName(),
            $data['id'],
            $context
        );

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $runId,
                'uri' => $data['uri'],
                'fileSize' => (int) $data['file_size'],
                'mediaId' => $converted['id'],
            ]
        );
        unset($data['uri'], $data['file_size']);

        if ($catalogId !== null) {
            $converted['catalogId'] = $catalogId;
        }

        $translation['id'] = $this->mappingService->createNewUuid(
            $profileId,
            MediaTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $locale,
            $context
        );
        unset($data['id']);

        $this->helper->convertValue($translation, 'name', $data, 'name');
        $this->helper->convertValue($translation, 'description', $data, 'description');

        $languageData = $this->mappingService->getLanguageUuid($profileId, $locale, $context);

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
