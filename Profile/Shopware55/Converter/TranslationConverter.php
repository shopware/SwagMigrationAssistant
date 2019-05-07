<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class TranslationConverter extends Shopware55Converter
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
     * @var string
     */
    private $runId;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return 'translation';
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
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();

        if (!isset($data['locale'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data',
                'Order-Entity could not converted cause of empty necessary field: locale.',
                [
                    'id' => $data['id'],
                    'entity' => 'Translation',
                    'fields' => ['locale'],
                ],
                1
            );

            return new ConvertStruct(null, $data);
        }

        switch ($data['objecttype']) {
            case 'article':
                return $this->createProductTranslation($data);
            case 'supplier':
                return $this->createManufacturerProductTranslation($data);
            case 'config_units':
                return $this->createUnitTranslation($data);
            case 'category':
                return $this->createCategoryTranslation($data);
            case 'configuratoroption':
                return $this->createConfiguratorOptionTranslation($data);
            case 'configuratorgroup':
                return $this->createConfiguratorOptionGroupTranslation($data);
            case 'propertyvalue':
                return $this->createPropertyValueTranslation($data);
            case 'propertyoption':
                return $this->createPropertyOptionTranslation($data);
        }

        $this->loggingService->addWarning(
            $this->runId,
            Shopware55LogTypes::NOT_CONVERTABLE_OBJECT_TYPE,
            'Not convert able object type',
            sprintf('Translation of object type "%s" could not be converted.', $data['objecttype']),
            [
                'objectType' => $data['objecttype'],
                'data' => $data,
            ]
        );

        return new ConvertStruct(null, $data);
    }

    private function createProductTranslation(array &$data): ConvertStruct
    {
        $sourceData = $data;
        $product = [];
        $product['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_container',
            $data['objectkey'],
            $this->context
        );

        if (!isset($product['id'])) {
            $product['id'] = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::PRODUCT . '_mainProduct',
                $data['objectkey'],
                $this->context
            );
        }

        if (!isset($product['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated product not found',
                'Mapping of "product" is missing, but it is a required association for "translation". Import "product" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'product',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'product',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }
        $product['entityDefinitionClass'] = ProductDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Product-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Product',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $productTranslation = [];
        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'txtArtikel':
                    $this->convertValue($productTranslation, 'name', $objectData, 'txtArtikel');
                    break;
                case 'txtshortdescription':
                    $this->convertValue($productTranslation, 'description', $objectData, 'txtshortdescription');
                    break;
                case 'txtpackunit':
                    $this->convertValue($productTranslation, 'packUnit', $objectData, 'txtpackunit');
                    break;
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $productTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $productTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_TRANSLATION,
            $data['id'],
            $this->context
        );
        unset($data['id'], $data['objectkey']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $productTranslation['languageId'] = $languageUuid;

        $product['translations'][$languageUuid] = $productTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($product, $data);
    }

    private function createManufacturerProductTranslation(array &$data): ConvertStruct
    {
        $sourceData = $data;
        $manufacturer = [];
        $manufacturer['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER,
            $data['objectkey'],
            $this->context
        );

        if (!isset($manufacturer['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated manufacturer not found',
                'Mapping of "manufacturer" is missing, but it is a required association for "translation". Import "product" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'manufacturer',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'product',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $manufacturer['entityDefinitionClass'] = ProductManufacturerDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Manufacturer-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Manufacturer',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $manufacturerTranslation = [];
        $manufacturerTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER_TRANSLATION,
            $data['id'],
            $this->context
        );
        unset($data['id'], $data['objectkey']);

        $this->convertValue($manufacturerTranslation, 'name', $data, 'name');

        foreach ($objectData as $key => $value) {
            if ($key === 'description') {
                $this->convertValue($manufacturerTranslation, 'description', $objectData, 'description');
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $manufacturerTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $manufacturerTranslation['languageId'] = $languageUuid;

        $manufacturer['translations'][$languageUuid] = $manufacturerTranslation;

        unset($data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($manufacturer, $data);
    }

    private function createUnitTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $unit = [];
        $unit['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::UNIT,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($unit['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated unit not found',
                'Mapping of "unit" is missing, but it is a required association for "translation". Import "product" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'unit',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'product',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $unit['entityDefinitionClass'] = UnitDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Unit-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Unit',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $unitTranslation = [];
        $unitTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::UNIT_TRANSLATION,
            $data['id'],
            $this->context
        );

        /** @var array $objectData */
        $objectData = array_pop($objectData);

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'unit':
                    $this->convertValue($unitTranslation, 'shortCode', $objectData, 'unit');
                    break;
                case 'description':
                    $this->convertValue($unitTranslation, 'name', $objectData, 'description');
                    break;
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $unitTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $unitTranslation['languageId'] = $languageUuid;

        $unit['translations'][$languageUuid] = $unitTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($unit, $data);
    }

    private function createCategoryTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $category = [];
        $category['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($category['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated category not found',
                'Mapping of "category" is missing, but it is a required association for "translation". Import "category" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'category',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'category',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $category['entityDefinitionClass'] = CategoryDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Category-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Category',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        // no equivalent in category translation definition
        unset(
            $objectData['streamId'],
            $objectData['external'],
            $objectData['externalTarget'],
            $objectData['cmsheadline'],
            $objectData['cmstext'],
            $objectData['metatitle'],
            $objectData['metadescription'],
            $objectData['metakeywords']
        );

        $categoryTranslation = [];
        $categoryTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CATEGORY_TRANSLATION,
            $data['id'],
            $this->context
        );

        if (isset($objectData['description'])) {
            $this->convertValue($categoryTranslation, 'name', $objectData, 'description');
        }

        foreach ($objectData as $key => $value) {
            if ($key === 'description') {
                $this->convertValue($categoryTranslation, 'name', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $categoryTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $categoryTranslation['languageId'] = $languageUuid;

        $category['translations'][$languageUuid] = $categoryTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($category, $data);
    }

    private function createConfiguratorOptionTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $configuratorOption = [];
        $configuratorOption['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_option',
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($configuratorOption['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated configuration group option not found',
                'Mapping of "configuration group option" is missing, but it is a required association for "translation". Import "configuration group option" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'Configuration group option',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'Configuration group option',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $configuratorOption['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Configuration-Group-Option-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Configuration group option',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyGroupOptionTranslation = [];
        $propertyGroupOptionTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION,
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'name') {
                $this->convertValue($propertyGroupOptionTranslation, 'name', $objectData, $key);
            }

            if ($key === 'position') {
                $this->convertValue($propertyGroupOptionTranslation, 'position', $objectData, $key, self::TYPE_INTEGER);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $propertyGroupOptionTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $propertyGroupOptionTranslation['languageId'] = $languageUuid;

        $configuratorOption['translations'][$languageUuid] = $propertyGroupOptionTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($configuratorOption, $data);
    }

    private function createConfiguratorOptionGroupTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $configuratorOptionGroup = [];
        $configuratorOptionGroup['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_option',
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($configuratorOptionGroup['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated configuration group not found',
                'Mapping of "configuration group" is missing, but it is a required association for "translation". Import "configuration group" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'Configuration group',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'Configuration group',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $configuratorOptionGroup['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Configuration-Group-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Configuration group',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyGroupTranslation = [];
        $propertyGroupTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TRANSLATION,
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'name') {
                $this->convertValue($propertyGroupTranslation, 'name', $objectData, $key);
            }

            if ($key === 'description') {
                $this->convertValue($propertyGroupTranslation, 'description', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $propertyGroupTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $propertyGroupTranslation['languageId'] = $languageUuid;

        $configuratorOptionGroup['translations'][$languageUuid] = $propertyGroupTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($configuratorOptionGroup, $data);
    }

    private function createPropertyValueTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $propertyValue = [];
        $propertyValue['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_property',
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($propertyValue['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated property value not found',
                'Mapping of "property value" is missing, but it is a required association for "translation". Import "property value" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'Property value',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'Property value',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyValue['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Property-Value-Option-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Property value',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyValueTranslation = [];
        $propertyValueTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION,
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'optionValue') {
                $this->convertValue($propertyValueTranslation, 'name', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $propertyValueTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $propertyValueTranslation['languageId'] = $languageUuid;

        $propertyValue['translations'][$languageUuid] = $propertyValueTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($propertyValue, $data);
    }

    private function createPropertyOptionTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $propertyOption = [];
        $propertyOption['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_property',
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if (!isset($propertyOption['id'])) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::ASSOCIATION_REQUIRED_MISSING,
                'Associated property option not found',
                'Mapping of "property option" is missing, but it is a required association for "translation". Import "property option" first.',
                [
                    'data' => $data,
                    'missingEntity' => 'Property option',
                    'requiredFor' => 'translation',
                    'missingImportEntity' => 'Property option',
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyOption['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Property-Option-Translation-Entity could not be converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Property option',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $propertyOptionTranslation = [];
        $propertyOptionTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TRANSLATION,
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'optionName') {
                $this->convertValue($propertyOptionTranslation, 'optionName', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $propertyOptionTranslation['customFields'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);
        $propertyOptionTranslation['languageId'] = $languageUuid;

        $propertyOption['translations'][$languageUuid] = $propertyOptionTranslation;

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($propertyOption, $data);
    }
}
