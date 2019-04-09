<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CategoryConverter extends AbstractConverter
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
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $oldCategoryId;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return CategoryDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->oldCategoryId = $data['id'];

        if (!isset($data['_locale'])) {
            $this->loggingService->addWarning(
                $migrationContext->getRunUuid(),
                Shopware55LogTypes::EMPTY_LOCALE,
                'Empty locale',
                'Category-Entity could not converted cause of empty locale.',
                ['id' => $this->oldCategoryId]
            );

            return new ConvertStruct(null, $data);
        }

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['path'], // will be generated
            $data['left'],
            $data['right'],
            $data['added'],
            $data['changed'],
            $data['stream_id'],
            $data['metakeywords'],
            $data['metadescription'],
            $data['cmsheadline'],
            $data['cmstext'],
            $data['meta_title'],

            // TODO check how to handle these
            $data['template'],
            $data['external_target'],
            $data['mediaID'],
            $data['asset']
        );

        if (isset($data['parent'])) {
            $parentUuid = $this->mappingService->getUuid(
                $this->connectionId,
                CategoryDefinition::getEntityName(),
                $data['parent'],
                $this->context
            );

            if ($parentUuid === null) {
                throw new ParentEntityForChildNotFoundException(CategoryDefinition::getEntityName(), $this->oldCategoryId);
            }

            $converted['parentId'] = $parentUuid;
        }
        unset($data['parent']);

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CategoryDefinition::getEntityName(),
            $this->oldCategoryId,
            $this->context
        );
        unset($data['id']);

        $this->helper->convertValue($converted, 'position', $data, 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'level', $data, 'level', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'active', $data, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'isBlog', $data, 'blog', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'external', $data, 'external');
        $this->helper->convertValue($converted, 'hideFilter', $data, 'hidefilter', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'hideTop', $data, 'hidetop', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'productBoxLayout', $data, 'product_box_layout');
        $this->helper->convertValue($converted, 'hideSortings', $data, 'hide_sortings', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'sortingIds', $data, 'sorting_ids');
        $this->helper->convertValue($converted, 'facetIds', $data, 'facet_ids');

        if (isset($data['attributes'])) {
            $converted['attributes'] = $this->getAttributes($data['attributes']);
        }
        unset($data['attributes']);

        $converted['translations'] = [];
        $this->setGivenCategoryTranslation($data, $converted);
        unset($data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function setGivenCategoryTranslation(array &$data, array &$converted): void
    {
        $originalData = $data;
        $this->helper->convertValue($converted, 'name', $data, 'description');

        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        if ($languageData['createData']['localeCode'] === $data['_locale']) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['categoryId'] = $converted['id'];

        $this->helper->convertValue($localeTranslation, 'name', $originalData, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            CategoryTranslationDefinition::getEntityName(),
            $this->oldCategoryId . ':' . $data['_locale'],
            $this->context
        );

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);
        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $localeTranslation['language']['id'] = $languageData['uuid'];
            $localeTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['translationCodeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $localeTranslation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $localeTranslation;
    }

    private function getAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute => $value) {
            if ($attribute === 'id' || $attribute === 'categoryID') {
                continue;
            }
            $result[CategoryDefinition::getEntityName() . '_' . $attribute] = $value;
        }

        return $result;
    }
}
