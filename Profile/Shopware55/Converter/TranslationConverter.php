<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Unit\Aggregate\UnitTranslation\UnitTranslationDefinition;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class TranslationConverter implements ConverterInterface
{
    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var Shopware55MappingService
     */
    private $mappingService;

    public function __construct(
        ConverterHelperService $converterHelperService,
        Shopware55MappingService $mappingService
    ) {
        $this->helper = $converterHelperService;
        $this->mappingService = $mappingService;
    }

    public function supports(): string
    {
        return 'translation';
    }

    public function convert(array $data, Context $context): ConvertStruct
    {
        switch ($data['objecttype']) {
            case 'article':
                return $this->createProductTranslation($data, $context);
            case 'supplier':
                return $this->createManufacturerProductTranslation($data, $context);
            case 'config_units':
                return $this->createUnitTranslation($data, $context);
            case 'category':
                return $this->createCategoryTranslation($data, $context);
        }

        return new ConvertStruct(null, $data);
    }

    private function createProductTranslation(array &$data, Context $context): ConvertStruct
    {
        $sourceData = $data;
        $productTranslation = [];
        $productTranslation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            ProductTranslationDefinition::getEntityName(),
            $data['id'],
            $context
        );
        $productTranslation['productId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            ProductDefinition::getEntityName() . '_container',
            $data['objectkey'],
            $context
        );

        if (!isset($productTranslation['productId'])) {
            $productTranslation['productId'] = $this->mappingService->getUuid(
                Shopware55Profile::PROFILE_NAME,
                ProductDefinition::getEntityName(),
                $data['objectkey'],
                $context
            );
        }

        if (!isset($productTranslation['productId'])) {
            return new ConvertStruct(null, $sourceData);
        }

        unset($data['id'], $data['objectkey']);
        $productTranslation['entityDefinitionClass'] = ProductTranslationDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            return new ConvertStruct(null, $sourceData);
        }

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'txtArtikel':
                    $this->helper->convertValue($productTranslation, 'name', $objectData, 'txtArtikel');
                    break;
                case 'txtlangbeschreibung':
                    $this->helper->convertValue($productTranslation, 'descriptionLong', $objectData, 'txtlangbeschreibung');
                    break;
                case 'txtshortdescription':
                    $this->helper->convertValue($productTranslation, 'description', $objectData, 'txtshortdescription');
                    break;
                case 'txtpackunit':
                    $this->helper->convertValue($productTranslation, 'packUnit', $objectData, 'txtpackunit');
                    break;
            }
        }

        $data['objectdata'] = serialize($objectData);
        if (empty($objectData)) {
            unset($data['objectdata']);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $data['_locale'], $context);

        if (isset($languageData['createData'])) {
            $productTranslation['language']['id'] = $languageData['uuid'];
            $productTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $productTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $productTranslation['languageId'] = $languageData['uuid'];
        }

        unset($data['name'], $data['_locale']);

        return new ConvertStruct($productTranslation, $data);
    }

    private function createManufacturerProductTranslation(array &$data, Context $context): ConvertStruct
    {
        $sourceData = $data;
        $manufacturerTranslation = [];
        $manufacturerTranslation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            ProductManufacturerTranslationDefinition::getEntityName(),
            $data['id'],
            $context
        );
        $manufacturerTranslation['productManufacturerId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            ProductManufacturerDefinition::getEntityName(),
            $data['objectkey'],
            $context
        );
        unset($data['id'], $data['objectkey']);

        if (!isset($manufacturerTranslation['productManufacturerId'])) {
            return new ConvertStruct(null, $sourceData);
        }

        $manufacturerTranslation['entityDefinitionClass'] = ProductManufacturerTranslationDefinition::class;
        $this->helper->convertValue($manufacturerTranslation, 'name', $data, 'name');

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            return new ConvertStruct(null, $sourceData);
        }

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'metaTitle':
                    $this->helper->convertValue($manufacturerTranslation, 'metaTitle', $objectData, 'metaTitle');
                    break;
                case 'description':
                    $this->helper->convertValue($manufacturerTranslation, 'description', $objectData, 'description');
                    break;
                case 'metaDescription':
                    $this->helper->convertValue($manufacturerTranslation, 'metaDescription', $objectData, 'metaDescription');
                    break;
                case 'metaKeywords':
                    $this->helper->convertValue($manufacturerTranslation, 'metaKeywords', $objectData, 'metaKeywords');
                    break;
            }
        }

        $data['objectdata'] = serialize($objectData);
        if (empty($objectData)) {
            unset($data['objectdata']);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $data['_locale'], $context);

        if (isset($languageData['createData'])) {
            $manufacturerTranslation['language']['id'] = $languageData['uuid'];
            $manufacturerTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $manufacturerTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $manufacturerTranslation['languageId'] = $languageData['uuid'];
        }

        unset($data['_locale']);

        return new ConvertStruct($manufacturerTranslation, $data);
    }

    private function createUnitTranslation(array $data, Context $context): ConvertStruct
    {
        $sourceData = $data;

        $unitTranslation = [];
        $unitTranslation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            UnitTranslationDefinition::getEntityName(),
            $data['id'],
            $context
        );
        $unitTranslation['unitId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            UnitDefinition::getEntityName(),
            $data['objectkey'],
            $context
        );
        unset($data['id'], $data['objectkey']);

        if (!isset($unitTranslation['unitId'])) {
            return new ConvertStruct(null, $sourceData);
        }

        $unitTranslation['entityDefinitionClass'] = UnitTranslationDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            return new ConvertStruct(null, $sourceData);
        }

        $objectData = array_pop($objectData);

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'unit':
                    $this->helper->convertValue($unitTranslation, 'shortCode', $objectData, 'unit');
                    break;
                case 'description':
                    $this->helper->convertValue($unitTranslation, 'name', $objectData, 'description');
                    break;
            }
        }

        $data['objectdata'] = serialize($objectData);
        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            return new ConvertStruct(null, $sourceData);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $data['_locale'], $context);

        if (isset($languageData['createData'])) {
            $unitTranslation['language']['id'] = $languageData['uuid'];
            $unitTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $unitTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $unitTranslation['languageId'] = $languageData['uuid'];
        }

        unset($data['name'], $data['_locale']);

        return new ConvertStruct($unitTranslation, $data);
    }

    private function createCategoryTranslation(array $data, Context $context): ConvertStruct
    {
        $sourceData = $data;

        $categoryTranslation = [];
        $categoryTranslation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            CategoryDefinition::getEntityName(),
            $data['id'],
            $context
        );
        $categoryTranslation['categoryId'] = $this->mappingService->getUuid(
            Shopware55Profile::PROFILE_NAME,
            CategoryDefinition::getEntityName(),
            $data['objectkey'],
            $context
        );
        unset($data['id'], $data['objectkey']);

        if (!isset($categoryTranslation['categoryId'])) {
            return new ConvertStruct(null, $sourceData);
        }

        $categoryTranslation['entityDefinitionClass'] = CategoryTranslationDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            return new ConvertStruct(null, $sourceData);
        }

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'description':
                    $this->helper->convertValue($categoryTranslation, 'name', $objectData, 'description');
                    break;
                case 'cmsheadline':
                    $this->helper->convertValue($categoryTranslation, 'cmsHeadline', $objectData, 'cmsheadline');
                    break;
                case 'cmstext':
                    $this->helper->convertValue($categoryTranslation, 'cmsDescription', $objectData, 'cmstext');
                    break;
                case 'metatitle':
                    $this->helper->convertValue($categoryTranslation, 'metaTitle', $objectData, 'metatitle');
                    break;
                case 'metadescription':
                    $this->helper->convertValue($categoryTranslation, 'metaDescription', $objectData, 'metadescription');
                    break;
                case 'metakeywords':
                    $this->helper->convertValue($categoryTranslation, 'metaKeywords', $objectData, 'metakeywords');
                    break;
            }
        }

        $data['objectdata'] = serialize($objectData);
        if (empty($objectData)) {
            unset($data['objectdata']);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $data['_locale'], $context);

        if (isset($languageData['createData'])) {
            $categoryTranslation['language']['id'] = $languageData['uuid'];
            $categoryTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $categoryTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $categoryTranslation['languageId'] = $languageData['uuid'];
        }

        unset($data['name'], $data['_locale']);

        return new ConvertStruct($categoryTranslation, $data);
    }
}
