<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use SwagMigrationAssistant\Migration\Logging\Log\InvalidUnserializedData;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedTranslationType;

abstract class TranslationConverter extends ShopwareConverter
{
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

    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->migrationContext = $migrationContext;
        $this->runId = $migrationContext->getRunUuid();

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

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
            new UnsupportedTranslationType(
                $migrationContext->getRunUuid(),
                $data['objecttype'],
                DefaultEntities::TRANSLATION,
                $data['id']
            )
        );

        return new ConvertStruct(null, $data);
    }

    protected function createProductTranslation(array &$data): ConvertStruct
    {
        $sourceData = $data;
        $product = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MAIN,
            $data['objectkey'],
            $this->context
        );

        if ($mapping === null) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_CONTAINER,
                $data['objectkey'],
                $this->context
            );
        }

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PRODUCT,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $product['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
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
                case 'txtlangbeschreibung':
                    $this->convertValue($productTranslation, 'description', $objectData, 'txtlangbeschreibung');
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

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $productTranslation['id'] = $this->mainMapping['entityUuid'];
        unset($data['id'], $data['objectkey']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);

        if ($languageUuid !== null) {
            $productTranslation['languageId'] = $languageUuid;
            $product['translations'][$languageUuid] = $productTranslation;
        }

        unset($data['name'], $data['locale']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($product, $returnData, $this->mainMapping['id']);
    }

    protected function createManufacturerProductTranslation(array &$data): ConvertStruct
    {
        $sourceData = $data;
        $manufacturer = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER,
            $data['objectkey'],
            $this->context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PRODUCT_MANUFACTURER,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $manufacturer['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $manufacturer['entityDefinitionClass'] = ProductManufacturerDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PRODUCT_MANUFACTURER_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $manufacturerTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $manufacturerTranslation['id'] = $this->mainMapping['entityUuid'];
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

        if ($languageUuid !== null) {
            $manufacturerTranslation['languageId'] = $languageUuid;
            $manufacturer['translations'][$languageUuid] = $manufacturerTranslation;
        }

        unset($data['locale']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($manufacturer, $returnData, $this->mainMapping['id']);
    }

    protected function createUnitTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $unit = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::UNIT,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::UNIT,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $unit['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $unit['entityDefinitionClass'] = UnitDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::UNIT_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $unitTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $unitTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $unitTranslation['languageId'] = $languageUuid;
            $unit['translations'][$languageUuid] = $unitTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($unit, $data, $this->mainMapping['id']);
    }

    protected function createCategoryTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $category = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::CATEGORY,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $category['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
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
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $categoryTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $categoryTranslation['languageId'] = $languageUuid;
            $category['translations'][$languageUuid] = $categoryTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($category, $data, $this->mainMapping['id']);
    }

    protected function createConfiguratorOptionTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $configuratorOption = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TYPE_OPTION,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PROPERTY_GROUP_OPTION,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $configuratorOption['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $configuratorOption['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $propertyGroupOptionTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context
        );
        $propertyGroupOptionTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $propertyGroupOptionTranslation['languageId'] = $languageUuid;
            $configuratorOption['translations'][$languageUuid] = $propertyGroupOptionTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($configuratorOption, $data, $this->mainMapping['id']);
    }

    protected function createConfiguratorOptionGroupTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $configuratorOptionGroup = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TYPE_OPTION,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PROPERTY_GROUP,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $configuratorOptionGroup['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $configuratorOptionGroup['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $propertyGroupTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $propertyGroupTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $propertyGroupTranslation['languageId'] = $languageUuid;
            $configuratorOptionGroup['translations'][$languageUuid] = $propertyGroupTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($configuratorOptionGroup, $data, $this->mainMapping['id']);
    }

    protected function createPropertyValueTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $propertyValue = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TYPE_PROPERTY,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PROPERTY_GROUP_OPTION,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $propertyValue['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $propertyValue['entityDefinitionClass'] = PropertyGroupOptionDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $propertyValueTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context
        );
        $propertyValueTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $propertyValueTranslation['languageId'] = $languageUuid;
            $propertyValue['translations'][$languageUuid] = $propertyValueTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($propertyValue, $data, $this->mainMapping['id']);
    }

    protected function createPropertyOptionTranslation(array $data): ConvertStruct
    {
        $sourceData = $data;

        $propertyOption = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TYPE_PROPERTY,
            $data['objectkey'],
            $this->context
        );
        unset($data['objectkey']);

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::PROPERTY_GROUP,
                    $data['id'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        $propertyOption['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $propertyOption['entityDefinitionClass'] = PropertyGroupDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::PROPERTY_GROUP_TRANSLATION);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $propertyOptionTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $propertyOptionTranslation['id'] = $this->mainMapping['entityUuid'];

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

        if ($languageUuid !== null) {
            $propertyOptionTranslation['languageId'] = $languageUuid;
            $propertyOption['translations'][$languageUuid] = $propertyOptionTranslation;
        }

        unset($data['name'], $data['locale']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($propertyOption, $data, $this->mainMapping['id']);
    }

    protected function getAttribute(string $entityName, string $key, string $value, array &$translation, array &$objectData): void
    {
        $connection = $this->migrationContext->getConnection();
        $connectionName = '';
        if ($connection !== null) {
            $connectionName = $connection->getName();
        }

        $isAttribute = mb_strpos($key, '__attribute_');
        if ($isAttribute !== false) {
            $key = 'migration_' . $connectionName . '_' . $entityName . '_' . str_replace('__attribute_', '', $key);
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
                    DefaultEntities::TRANSLATION,
                    $data['id'],
                    $entity,
                    $data['objectdata']
                )
            );

            return null;
        }

        return $objectData;
    }
}
