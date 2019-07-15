<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\InvalidUnserializedData;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedObjectType;

abstract class TranslationConverter extends ShopwareConverter
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
     * @var string
     */
    protected $runId;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
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
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::TRANSLATION,
                $data['id'],
                'locale'
            ));

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

        $this->loggingService->addLogEntry(
            new UnsupportedObjectType(
                $migrationContext->getRunUuid(),
                $data['objecttype'],
                DefaultEntities::TRANSLATION,
                $data['id']
        ));

        return new ConvertStruct(null, $data);
    }

    protected function createProductTranslation(array &$data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PRODUCT,
                    $data['id']
            ));

            return new ConvertStruct(null, $sourceData);
        }
        $product['entityDefinitionClass'] = ProductDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PRODUCT_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PRODUCT, $key, $value, $productTranslation, $objectData);
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

    protected function createManufacturerProductTranslation(array &$data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PRODUCT_MANUFACTURER,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $manufacturer['entityDefinitionClass'] = ProductManufacturerDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PRODUCT_MANUFACTURER_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PRODUCT_MANUFACTURER, $key, $value, $manufacturerTranslation, $objectData);
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

    protected function createUnitTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::UNIT,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $unit['entityDefinitionClass'] = UnitDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::UNIT_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::UNIT, $key, $value, $unitTranslation, $objectData);
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

    protected function createCategoryTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::CATEGORY,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $category['entityDefinitionClass'] = CategoryDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::CATEGORY_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::CATEGORY, $key, $value, $categoryTranslation, $objectData);
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

    protected function createConfiguratorOptionTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PROPERTY_GROUP_OPTION,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $configuratorOption['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PROPERTY_GROUP_OPTION, $key, $value, $propertyGroupOptionTranslation, $objectData);
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

    protected function createConfiguratorOptionGroupTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PROPERTY_GROUP,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $configuratorOptionGroup['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PROPERTY_GROUP, $key, $value, $propertyGroupTranslation, $objectData);
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

    protected function createPropertyValueTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PROPERTY_GROUP_OPTION,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $propertyValue['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PROPERTY_GROUP_OPTION, $key, $value, $propertyValueTranslation, $objectData);
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

    protected function createPropertyOptionTranslation(array $data): ConvertStruct
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
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    DefaultEntities::PROPERTY_GROUP,
                    $data['id']
                ));

            return new ConvertStruct(null, $sourceData);
        }

        $propertyOption['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_TRANSLATION);
        if ($objectData === null) {
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

            $this->getAttribute(DefaultEntities::PROPERTY_GROUP, $key, $value, $propertyOptionTranslation, $objectData);
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

    protected function getAttribute(string $entityName, string $key, string $value, array &$translation, array &$objectData): void
    {
        $isAttribute = strpos($key, '__attribute_');
        if ($isAttribute !== false) {
            $key = $entityName . '_' . str_replace('__attribute_', '', $key);
            $translation['customFields'][$key] = $value;
            unset($objectData[$key]);
        }
    }

    protected function unserializeTranslation(array $data, string $entity): ?array
    {
        try {
            $objectData = unserialize($data['objectdata'], ['allowed_classes' => false]);
        } catch (\Exception $error) {
            $objectData = null;
        }

        if (!\is_array($objectData)) {
            $this->loggingService->addLogEntry(
                new InvalidUnserializedData(
                    $this->runId,
                    $entity,
                    $data['objectdata'],
                    DefaultEntities::TRANSLATION,
                    $data['id']
            ));

            return null;
        }

        return $objectData;
    }
}
