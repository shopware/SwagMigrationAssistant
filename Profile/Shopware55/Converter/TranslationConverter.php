<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Configuration\Aggregate\ConfigurationGroupOption\ConfigurationGroupOptionDefinition;
use Shopware\Core\Content\Configuration\Aggregate\ConfigurationGroupOptionTranslation\ConfigurationGroupOptionTranslationDefinition;
use Shopware\Core\Content\Configuration\Aggregate\ConfigurationGroupTranslation\ConfigurationGroupTranslationDefinition;
use Shopware\Core\Content\Configuration\ConfigurationGroupDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Unit\Aggregate\UnitTranslation\UnitTranslationDefinition;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class TranslationConverter extends AbstractConverter
{
    /**
     * @var ConverterHelperService
     */
    private $helper;

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
        ConverterHelperService $converterHelperService,
        LoggingServiceInterface $loggingService
    ) {
        $this->helper = $converterHelperService;
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
        }

        $this->loggingService->addWarning(
            $this->runId,
            Shopware55LogTypes::NOT_CONVERTABLE_OBJECT_TYPE,
            'Not convert able object type',
            sprintf('Translation of object type "%s" could not converted.', $data['objecttype']),
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
            ProductDefinition::getEntityName() . '_container',
            $data['objectkey'],
            $this->context
        );

        if (!isset($product['id'])) {
            $product['id'] = $this->mappingService->getUuid(
                $this->connectionId,
                ProductDefinition::getEntityName() . '_mainProduct',
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
                'Product-Translation-Entity could not converted cause of invalid unserialized object data.',
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
                    $this->helper->convertValue($productTranslation, 'name', $objectData, 'txtArtikel');
                    break;
                case 'txtshortdescription':
                    $this->helper->convertValue($productTranslation, 'description', $objectData, 'txtshortdescription');
                    break;
                case 'txtpackunit':
                    $this->helper->convertValue($productTranslation, 'packUnit', $objectData, 'txtpackunit');
                    break;
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $productTranslation['attributes'][$key] = $value;
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
            ProductTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );
        unset($data['id'], $data['objectkey']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $productTranslation['language']['id'] = $languageData['uuid'];
            $productTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $productTranslation['language']['translationCodeId'] = $languageData['createData']['localeId'];
            $productTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $productTranslation['languageId'] = $languageData['uuid'];
        }

        $product['translations'][$languageData['uuid']] = $productTranslation;

        unset($data['name'], $data['_locale']);

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
            ProductManufacturerDefinition::getEntityName(),
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
                'Manufacturer-Translation-Entity could not converted cause of invalid unserialized object data.',
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
            ProductManufacturerTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );
        unset($data['id'], $data['objectkey']);

        $this->helper->convertValue($manufacturerTranslation, 'name', $data, 'name');

        foreach ($objectData as $key => $value) {
            if ($key === 'description') {
                $this->helper->convertValue($manufacturerTranslation, 'description', $objectData, 'description');
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $manufacturerTranslation['attributes'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $manufacturerTranslation['language']['id'] = $languageData['uuid'];
            $manufacturerTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $manufacturerTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $manufacturerTranslation['languageId'] = $languageData['uuid'];
        }

        $manufacturer['translations'][$languageData['uuid']] = $manufacturerTranslation;

        unset($data['_locale']);

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
            UnitDefinition::getEntityName(),
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
                'Unit-Translation-Entity could not converted cause of invalid unserialized object data.',
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
            UnitTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );

        /** @var array $objectData */
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

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $unitTranslation['attributes'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $unitTranslation['language']['id'] = $languageData['uuid'];
            $unitTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $unitTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $unitTranslation['languageId'] = $languageData['uuid'];
        }

        $unit['translations'][$languageData['uuid']] = $unitTranslation;

        unset($data['name'], $data['_locale']);

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
            CategoryDefinition::getEntityName(),
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
                'Category-Translation-Entity could not converted cause of invalid unserialized object data.',
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
            CategoryTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );

        if (isset($objectData['description'])) {
            $this->helper->convertValue($categoryTranslation, 'name', $objectData, 'description');
        }

        foreach ($objectData as $key => $value) {
            if ($key === 'description') {
                $this->helper->convertValue($categoryTranslation, 'name', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $categoryTranslation['attributes'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $categoryTranslation['language']['id'] = $languageData['uuid'];
            $categoryTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $categoryTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $categoryTranslation['languageId'] = $languageData['uuid'];
        }

        $category['translations'][$languageData['uuid']] = $categoryTranslation;

        unset($data['name'], $data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($category, $data);
    }

    private function createConfiguratorOptionTranslation(array $data)
    {
        $sourceData = $data;

        $configuratorOption = [];
        $configuratorOption['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            ConfigurationGroupOptionDefinition::getEntityName(),
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

        $configuratorOption['entityDefinitionClass'] = ConfigurationGroupOptionDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Configuration-Group-Option-Translation-Entity could not converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Configuration group option',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $configurationGroupOptionTranslation = [];
        $configurationGroupOptionTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ConfigurationGroupOptionTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'name') {
                $this->helper->convertValue($configurationGroupOptionTranslation, 'name', $objectData, $key);
            }

            if ($key === 'position') {
                $this->helper->convertValue($configurationGroupOptionTranslation, 'position', $objectData, $key, ConverterHelperService::TYPE_INTEGER);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $configurationGroupOptionTranslation['attributes'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $configurationGroupOptionTranslation['language']['id'] = $languageData['uuid'];
            $configurationGroupOptionTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $configurationGroupOptionTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $configurationGroupOptionTranslation['languageId'] = $languageData['uuid'];
        }

        $configuratorOption['translations'][$languageData['uuid']] = $configurationGroupOptionTranslation;

        unset($data['name'], $data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($configuratorOption, $data);
    }

    private function createConfiguratorOptionGroupTranslation(array $data)
    {
        $sourceData = $data;

        $configuratorOptionGroup = [];
        $configuratorOptionGroup['id'] = $this->mappingService->getUuid(
            $this->connectionId,
            ConfigurationGroupDefinition::getEntityName(),
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

        $configuratorOptionGroup['entityDefinitionClass'] = ConfigurationGroupDefinition::class;

        $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);

        if (!\is_array($objectData)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::INVALID_UNSERIALIZED_DATA,
                'Invalid unserialized data',
                'Configuration-Group-Translation-Entity could not converted cause of invalid unserialized object data.',
                [
                    'entity' => 'Configuration group',
                    'data' => $data['objectdata'],
                ]
            );

            return new ConvertStruct(null, $sourceData);
        }

        $configurationGroupTranslation = [];
        $configurationGroupTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ConfigurationGroupTranslationDefinition::getEntityName(),
            $data['id'],
            $this->context
        );

        foreach ($objectData as $key => $value) {
            if ($key === 'name') {
                $this->helper->convertValue($configurationGroupTranslation, 'name', $objectData, $key);
            }

            if ($key === 'description') {
                $this->helper->convertValue($configurationGroupTranslation, 'description', $objectData, $key);
            }

            $isAttribute = strpos($key, '__attribute_');
            if ($isAttribute !== false) {
                $key = str_replace('__attribute_', '', $key);
                $configurationGroupTranslation['attributes'][$key] = $value;
                unset($objectData[$key]);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectkey'], $data['objectlanguage'], $data['dirty']);

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData'])) {
            $configurationGroupTranslation['language']['id'] = $languageData['uuid'];
            $configurationGroupTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $configurationGroupTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $configurationGroupTranslation['languageId'] = $languageData['uuid'];
        }

        $configuratorOptionGroup['translations'][$languageData['uuid']] = $configurationGroupTranslation;

        unset($data['name'], $data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($configuratorOptionGroup, $data);
    }
}
