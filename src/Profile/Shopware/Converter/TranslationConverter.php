<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\InvalidUnserializedData;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedTranslationType;

#[Package('services-settings')]
abstract class TranslationConverter extends ShopwareConverter
{
    protected string $connectionId = '';

    protected Context $context;

    protected string $runId = '';

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
            case 'variant':
                return $this->createProductVariantTranslation($data);
            case 'supplier':
                return $this->createManufacturerProductTranslation($data);
            case 'config_units':
                return $this->createUnitTranslation($data);
            case 's_categories_attributes':
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
            case 'articleimage':
                return $this->createProductMediaTranslation($data);
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

    /**
     * @param array<string, mixed> $data
     */
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
                case 'txtshortdescription':
                    $this->convertValue($productTranslation, 'metaDescription', $objectData, 'txtshortdescription');

                    if (isset($productTranslation['metaDescription'])) {
                        // meta description is limited to 255 characters in Shopware 6
                        $productTranslation['metaDescription'] = \mb_substr($productTranslation['metaDescription'], 0, 255);
                    }

                    break;
                case 'txtkeywords':
                    $this->convertValue($productTranslation, 'keywords', $objectData, 'txtkeywords');

                    break;
                case 'metaTitle':
                    $this->convertValue($productTranslation, 'metaTitle', $objectData, 'metaTitle');

                    break;
            }

            $this->addAttribute(DefaultEntities::PRODUCT, $key, $value, $productTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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
        unset($data['id']);

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

        return new ConvertStruct($product, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createProductVariantTranslation(array &$data): ConvertStruct
    {
        if (!isset($data['ordernumber'])) {
            return new ConvertStruct(null, $data);
        }
        $sourceData = $data;
        $product = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $data['ordernumber'],
            $this->context
        );
        unset($data['ordernumber']);

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
            $this->addAttribute(DefaultEntities::PRODUCT, $key, $value, $productTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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
        unset($data['id']);

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

        return new ConvertStruct($product, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createManufacturerProductTranslation(array $data): ConvertStruct
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

            $this->addAttribute(DefaultEntities::PRODUCT_MANUFACTURER, $key, $value, $manufacturerTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($manufacturer, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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

        $objectData = \array_pop($objectData);

        foreach ($objectData as $key => $value) {
            switch ($key) {
                case 'unit':
                    $this->convertValue($unitTranslation, 'shortCode', $objectData, 'unit');

                    break;
                case 'description':
                    $this->convertValue($unitTranslation, 'name', $objectData, 'description');

                    break;
            }

            $this->addAttribute(DefaultEntities::UNIT, $key, $value, $unitTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($unit, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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
            $objectData['externalTarget'],
            $objectData['cmsheadline'],
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

        $this->convertValue($categoryTranslation, 'name', $objectData, 'description');
        $this->convertValue($categoryTranslation, 'description', $objectData, 'cmstext');
        $this->convertValue($categoryTranslation, 'externalLink', $objectData, 'external');
        $this->convertValue($categoryTranslation, 'metaTitle', $objectData, 'metatitle');
        $this->convertValue($categoryTranslation, 'metaDescription', $objectData, 'metadescription');
        $this->convertValue($categoryTranslation, 'keywords', $objectData, 'metakeywords');

        if (isset($categoryTranslation['metaDescription'])) {
            // meta description is limited to 255 characters in Shopware 6
            $categoryTranslation['metaDescription'] = \mb_substr($categoryTranslation['metaDescription'], 0, 255);
        }

        foreach ($objectData as $key => $value) {
            if ($key === 'description') {
                $this->convertValue($categoryTranslation, 'name', $objectData, $key);
            }
            $this->addAttribute(DefaultEntities::CATEGORY, $key, $value, $categoryTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($category, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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
            $this->addAttribute(DefaultEntities::PROPERTY_GROUP_OPTION, $key, $value, $propertyGroupOptionTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($configuratorOption, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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

            $this->addAttribute(DefaultEntities::PROPERTY_GROUP, $key, $value, $propertyGroupTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($configuratorOptionGroup, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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

            $this->addAttribute(DefaultEntities::PROPERTY_GROUP_OPTION, $key, $value, $propertyValueTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($propertyValue, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
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

            $this->addAttribute(DefaultEntities::PROPERTY_GROUP, $key, $value, $propertyOptionTranslation, $objectData);
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
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

        return new ConvertStruct($propertyOption, $data, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $translation
     * @param array<string, mixed> $objectData
     */
    protected function addAttribute(string $entityName, string $key, string $value, array &$translation, array &$objectData): void
    {
        $connection = $this->migrationContext->getConnection();

        if ($connection === null || $value === '') {
            return;
        }

        $connectionName = $connection->getName();
        $connectionName = \str_replace(' ', '', $connectionName);
        $connectionName = \preg_replace('/[^A-Za-z0-9\-]/', '', $connectionName);

        $isAttribute = \mb_strpos($key, '__attribute_');
        if ($isAttribute !== false) {
            $identifier = \str_replace('__attribute_', '', $key);
            $newKey = 'migration_' . $connectionName . '_' . $entityName . '_' . $identifier;

            $mapping = $this->mappingService->getMapping(
                $connection->getId(),
                $entityName . '_custom_field',
                $identifier,
                $this->context
            );

            if ($mapping !== null) {
                $this->mappingIds[] = $mapping['id'];

                if (isset($mapping['additionalData']['columnType'])
                    && \in_array($mapping['additionalData']['columnType'], ['text', 'string'], true)
                    && $value !== \strip_tags($value)
                ) {
                    return;
                }

                if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'boolean') {
                    $value = (bool) $value;
                }

                if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'integer') {
                    $value = (int) $value;
                }

                if (isset($mapping['additionalData']['columnType']) && $mapping['additionalData']['columnType'] === 'float') {
                    $value = (float) $value;
                }
            }

            $translation['customFields'][$newKey] = $value;
            unset($objectData[$key]);
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    protected function unserializeTranslation(array $data, string $entity): ?array
    {
        $objectDataSerialized = $data['objectdata'];

        try {
            $objectData = \unserialize($objectDataSerialized, ['allowed_classes' => false]);
        } catch (\Throwable $error) {
            $objectData = null;
        }

        if (!\is_array($objectData)) {
            $this->loggingService->addLogEntry(
                new InvalidUnserializedData(
                    $this->runId,
                    DefaultEntities::TRANSLATION,
                    $data['id'],
                    $entity,
                    $objectDataSerialized
                )
            );

            return null;
        }

        return $objectData;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createProductMediaTranslation(array $data): ConvertStruct
    {
        if (!isset($data['mediaId'])) {
            return new ConvertStruct(null, $data);
        }
        $sourceData = $data;

        $media = [];
        $mapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $data['mediaId'],
            $this->context
        );

        if ($mapping === null) {
            $this->loggingService->addLogEntry(
                new AssociationRequiredMissingLog(
                    $this->runId,
                    DefaultEntities::MEDIA,
                    $data['mediaId'],
                    DefaultEntities::TRANSLATION
                )
            );

            return new ConvertStruct(null, $sourceData);
        }
        unset($data['objectkey'], $data['mediaId']);

        $media['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];
        $media['entityDefinitionClass'] = MediaDefinition::class;

        $objectData = $this->unserializeTranslation($data, DefaultEntities::MEDIA);
        if ($objectData === null) {
            return new ConvertStruct(null, $sourceData);
        }

        $mediaTranslation = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $mediaTranslation['id'] = $this->mainMapping['entityUuid'];

        foreach (\array_keys($objectData) as $key) {
            if ($key === 'description') {
                $this->convertValue($mediaTranslation, 'alt', $objectData, $key);
            }
        }

        if (empty($objectData)) {
            unset($data['objectdata']);
        } else {
            $data['objectdata'] = \serialize($objectData);
        }

        unset($data['id'], $data['objecttype'], $data['objectlanguage'], $data['dirty']);

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['locale'], $this->context);

        if ($languageUuid !== null) {
            $mediaTranslation['languageId'] = $languageUuid;
            $media['translations'][$languageUuid] = $mediaTranslation;
        }

        unset($data['name'], $data['locale'], $data['ordernumber']);

        if (empty($data)) {
            $data = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($media, $data, $this->mainMapping['id'] ?? null);
    }
}
