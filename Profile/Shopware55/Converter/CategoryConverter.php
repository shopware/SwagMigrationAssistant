<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;

class CategoryConverter implements ConverterInterface
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
     * @var string
     */
    private $profileId;

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
        Shopware55MappingService $mappingService,
        ConverterHelperService $converterHelperService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->loggingService = $loggingService;
    }

    public function supports(): string
    {
        return CategoryDefinition::getEntityName();
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
        string $runId,
        string $profileId,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ): ConvertStruct {
        $this->profileId = $profileId;
        $this->context = $context;
        $this->oldCategoryId = $data['id'];

        if (!isset($data['_locale'])) {
            $this->loggingService->addWarning(
                $runId,
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

            // TODO check how to handle these
            $data['attributes'],
            $data['template'],
            $data['external_target'],
            $data['mediaID'],
            $data['asset']
        );

        if (isset($data['parent'])) {
            $parentUuid = $this->mappingService->getUuid(
                $this->profileId,
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
            $this->profileId,
            CategoryDefinition::getEntityName(),
            $this->oldCategoryId,
            $this->context
        );
        unset($data['id']);

        if ($catalogId !== null) {
            $converted['catalogId'] = $catalogId;
        }

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
        $defaultTranslation = [];
        $defaultTranslation['id'] = $this->mappingService->createNewUuid(
            $this->profileId,
            CategoryTranslationDefinition::getEntityName(),
            $this->oldCategoryId . ':' . $data['_locale'],
            $this->context
        );
        $defaultTranslation['categoryId'] = $converted['id'];

        $this->helper->convertValue($defaultTranslation, 'name', $data, 'description');
        $this->helper->convertValue($defaultTranslation, 'metaKeywords', $data, 'metakeywords');
        $this->helper->convertValue($defaultTranslation, 'metaTitle', $data, 'meta_title');
        $this->helper->convertValue($defaultTranslation, 'metaDescription', $data, 'metadescription');
        $this->helper->convertValue($defaultTranslation, 'cmsHeadline', $data, 'cmsheadline');
        $this->helper->convertValue($defaultTranslation, 'cmsDescription', $data, 'cmstext');

        $languageData = $this->mappingService->getLanguageUuid($this->profileId, $data['_locale'], $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $defaultTranslation['language']['id'] = $languageData['uuid'];
            $defaultTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $defaultTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $defaultTranslation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $defaultTranslation;
    }
}
