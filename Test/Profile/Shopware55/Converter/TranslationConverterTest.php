<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

class TranslationConverterTest extends TestCase
{
    /**
     * @var Shopware55TranslationConverter
     */
    private $translationConverter;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var MigrationContextInterface
     */
    private $productMigrationContext;

    /**
     * @var MigrationContextInterface
     */
    private $categoryMigrationContext;

    /**
     * @var string
     */
    private $connectionId;

    protected function setUp(): void
    {
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->translationConverter = new Shopware55TranslationConverter($this->mappingService, $this->loggingService);

        $this->connectionId = Uuid::randomHex();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($this->connectionId);
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setName('Shopware 5.5');
        $connection->setCredentialFields([]);

        $this->runId = Uuid::randomHex();

        $profile = new Shopware55Profile();

        $this->migrationContext = new MigrationContext(
            $profile,
            $connection,
            $this->runId,
            new TranslationDataSet(),
            0,
            250
        );

        $this->productMigrationContext = new MigrationContext(
            $profile,
            $connection,
            $this->runId,
            new ProductDataSet(),
            0,
            250
        );

        $this->categoryMigrationContext = new MigrationContext(
            $profile,
            $connection,
            $this->runId,
            new CategoryDataSet(),
            0,
            250
        );

        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CURRENCY, 'EUR', Context::createDefaultContext(), null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->translationConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvertUnknownTranslationType(): void
    {
        $context = Context::createDefaultContext();
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['invalid'], $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_UNSUPPORTED_OBJECT_TYPE');
        static::assertSame($logs[0]['parameters']['sourceId'], '276');
        static::assertSame($logs[0]['parameters']['objectType'], 'invalid');
    }

    public function testConvertProductTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['product'], $context, $this->migrationContext);

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertProductAttributeTranslation(): void
    {
        $context = Context::createDefaultContext();
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::PRODUCT_MAIN, '6', $context);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::PRODUCT . '_custom_field', 'checkboxTrue', $context, null, ['columnType' => 'boolean']);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::PRODUCT . '_custom_field', 'checkboxFalse', $context, null, ['columnType' => 'boolean']);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::PRODUCT . '_custom_field', 'int', $context, null, ['columnType' => 'integer']);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::PRODUCT . '_custom_field', 'float', $context, null, ['columnType' => 'float']);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['product'], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertCount(0, $this->loggingService->getLoggingArray());

        $customFields = $converted['translations'][$this->mappingService::DEFAULT_LANGUAGE_UUID]['customFields'];
        $expected = [
            'migration_Shopware55_product_checkboxTrue' => true,
            'migration_Shopware55_product_checkboxFalse' => false,
            'migration_Shopware55_product_int' => 100,
            'migration_Shopware55_product_float' => 55.23,
        ];
        static::assertSame($expected, $customFields);
        static::assertNull($convertResult->getUnmapped());
    }

    public function testConvertManufacturerTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConvertResult = $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['manufacturer'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertCount(1, $convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('objectdata', $convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedProduct['manufacturer']['id'], $converted['id']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertManufacturerTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext();

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['manufacturer'], $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());
    }

    public function testConvertManufacturerTranslationWithInvalidTranslationObject(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['manufacturer'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA');
        static::assertSame($logs[0]['parameters']['sourceId'], '273');
        static::assertSame($logs[0]['parameters']['unserializedEntity'], 'product_manufacturer_translation');
    }

    public function testConvertUnitTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConvertResult = $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['unit'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedProduct['unit']['id'], $converted['id']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertUnitTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext();

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['unit'], $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_UNIT');
        static::assertSame($logs[0]['parameters']['sourceId'], '274');
        static::assertSame($logs[0]['parameters']['missingEntity'], 'unit');
    }

    public function testConvertUnitTranslationWithInvalidTranslationObject(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['unit'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA');
        static::assertSame($logs[0]['parameters']['sourceId'], '274');
        static::assertSame($logs[0]['parameters']['unserializedEntity'], 'unit_translation');
    }

    public function testConvertCategoryTranslation(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext();
        $mediaFileService = new DummyMediaFileService();

        $categoryConverter = new Shopware55CategoryConverter($this->mappingService, $this->loggingService, $mediaFileService);
        $categoryConvertResult = $categoryConverter->convert($categoryData[1], $context, $this->categoryMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['category'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedCategory = $categoryConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedCategory['id'], $converted['id']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCategoryTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext();

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['category'], $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_CATEGORY');
        static::assertSame($logs[0]['parameters']['sourceId'], '275');
        static::assertSame($logs[0]['parameters']['missingEntity'], 'category');
    }

    public function testConvertCategoryTranslationWithInvalidTranslationObject(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext();
        $mediaFileService = new DummyMediaFileService();

        $categoryConverter = new Shopware55CategoryConverter($this->mappingService, $this->loggingService, $mediaFileService);
        $categoryConverter->convert($categoryData[1], $context, $this->categoryMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['category'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__SHOPWARE_INVALID_UNSERIALIZED_DATA');
        static::assertSame($logs[0]['parameters']['sourceId'], '275');
        static::assertSame($logs[0]['parameters']['unserializedEntity'], 'category_translation');
    }

    public function testCreateConfiguratorOptionTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_option',
            '11',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['configuratoroption'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupOptionDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('My test option', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreateConfiguratorOptionGroupTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_option',
            '5',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['configuratorgroup'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('My test group', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreatePropertyValueTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_property',
            '31',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['propertyvalue'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupOptionDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('gold', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreatePropertyOptionTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_property',
            '8',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['propertyoption'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('Size', $converted['translations'][$defaultLanguage]['optionName']);
    }

    public function testConvertProductWithoutLocale(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['productnolocale'], $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());
        static::assertCount(1, $this->loggingService->getLoggingArray());
        $logs = $this->loggingService->getLoggingArray();
        static::assertSame('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_TRANSLATION', $logs[0]['code']);
    }

    public function testConvertVariantAttributeTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productMapping = $this->mappingService->createMapping($this->connectionId, ProductDataSet::getEntity(), 'SW10006', null, null, Uuid::randomHex());
        $translationMapping = $this->mappingService->createMapping($this->connectionId, TranslationDataSet::getEntity(), '666', null, null, Uuid::randomHex());
        $productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, new DummyMediaFileService());
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['variant'], $context, $this->migrationContext);
        /** @var array $converted */
        $converted = $convertResult->getConverted();

        $expected = [
            'id' => $productMapping['entityUuid'],
            'entityDefinitionClass' => ProductDefinition::class,
            'translations' => [
                DummyMappingService::DEFAULT_LANGUAGE_UUID => [
                    'customFields' => [
                        'migration_Shopware55_product_attr1' => 'My free textfield',
                    ],
                    'id' => $translationMapping['entityUuid'],
                    'languageId' => DummyMappingService::DEFAULT_LANGUAGE_UUID,
                ],
            ],
        ];

        static::assertNotNull($convertResult->getMappingUuid());
        static::assertCount(0, $this->loggingService->getLoggingArray());
        static::assertArrayHasKey('translations', $converted);
        static::assertSame($expected, $converted);
    }

    public function testConvertProductMediaTranslation(): void
    {
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['articleimage'];

        $context = Context::createDefaultContext();
        $mediaMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $translationData['mediaId'],
            $context
        );
        $translationMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::TRANSLATION,
            $translationData['id'],
            $context
        );

        $expectedData = [
            'id' => $mediaMapping['entityUuid'],
            'entityDefinitionClass' => MediaDefinition::class,
            'translations' => [
                DummyMappingService::DEFAULT_LANGUAGE_UUID => [
                    'id' => $translationMapping['entityUuid'],
                    'alt' => 'EN - Nice Spachtelmasse',
                    'languageId' => DummyMappingService::DEFAULT_LANGUAGE_UUID,
                ],
            ],
        ];

        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertCount(0, $this->loggingService->getLoggingArray());
        static::assertSame($expectedData, $converted);
    }

    public function testConvertProductMediaTranslationWithoutMediaId(): void
    {
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['articleimage'];
        unset($translationData['mediaId']);

        $context = Context::createDefaultContext();
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        $logs = $this->loggingService->getLoggingArray();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getMappingUuid());
        static::assertNull($converted);
        static::assertCount(0, $logs);
    }

    public function testConvertProductMediaTranslationWithoutMediaMapping(): void
    {
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['articleimage'];

        $context = Context::createDefaultContext();
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        $logs = $this->loggingService->getLoggingArray();

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getMappingUuid());
        static::assertNull($converted);
        static::assertCount(1, $logs);

        $logParameters = [
            'missingEntity' => DefaultEntities::MEDIA,
            'requiredFor' => DefaultEntities::TRANSLATION,
            'sourceId' => '769',
        ];

        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_MEDIA', $logs[0]['code']);
        static::assertSame($logParameters, $logs[0]['parameters']);
    }
}
