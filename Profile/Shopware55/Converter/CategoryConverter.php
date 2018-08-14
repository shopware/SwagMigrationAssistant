<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class CategoryConverter implements ConverterInterface
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
    private $profile;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $oldCategoryId;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
    }

    public function supports(): string
    {
        return CategoryDefinition::getEntityName();
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(array $data, Context $context): ConvertStruct
    {
        $this->profile = Shopware55Profile::PROFILE_NAME;
        $this->context = $context;
        $this->oldCategoryId = $data['id'];

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
            $data['mediaID']
        );

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->profile,
            CategoryDefinition::getEntityName(),
            $data['id'],
            $this->context
        );
        unset($data['id']);

        if (isset($data['parent'])) {
            $parentUuid = $this->mappingService->getUuid(
                CategoryDefinition::getEntityName(),
                $data['parent'],
                $this->context
            );

            if ($parentUuid === null) {
                throw new ParentEntityForChildNotFoundException(CategoryDefinition::getEntityName());
            }

            $converted['parentId'] = $parentUuid;
        }
        unset($data['parent']);

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

    private function setGivenCategoryTranslation(array &$data, array &$converted)
    {
        $defaultTranslation = [];
        $defaultTranslation['id'] = $this->mappingService->createNewUuid(
            $this->profile,
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

        $languageData = $this->mappingService->getLanguageUuid($this->profile, $data['_locale'], $this->context);

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
