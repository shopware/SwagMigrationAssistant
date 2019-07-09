<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
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
     * @var string
     */
    private $profileId;

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

    protected function setUp(): void
    {
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->translationConverter = new Shopware55TranslationConverter($this->mappingService, $this->loggingService);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $this->runId = Uuid::randomHex();
        $this->profileId = Uuid::randomHex();

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

        $this->mappingService->createNewUuid($connection->getId(), DefaultEntities::CURRENCY, 'EUR', Context::createDefaultContext(), [], Uuid::randomHex());
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
        $description = 'Translation of object type "invalid" could not be converted.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertProductTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['product'], $context, $this->migrationContext);

        static::assertNull($convertResult->getUnmapped());
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertManufacturerTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConvertResult = $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['manufacturer'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertCount(1, $convertResult->getUnmapped());
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

        $productConverter = new Shopware55ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['manufacturer'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Manufacturer-Translation-Entity could not be converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConvertResult = $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['unit'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
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
        $description = 'Mapping of "unit" is missing, but it is a required association for "translation". Import "product" first.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslationWithInvalidTranslationObject(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new Shopware55ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['unit'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Unit-Translation-Entity could not be converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslation(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext();
        $mediaFileService = new DummyMediaFileService();

        $categoryConverter = new Shopware55CategoryConverter($this->mappingService, $mediaFileService, $this->loggingService);
        $categoryConvertResult = $categoryConverter->convert($categoryData[1], $context, $this->categoryMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['category'], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedCategory = $categoryConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
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
        $description = 'Mapping of "category" is missing, but it is a required association for "translation". Import "category" first.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslationWithInvalidTranslationObject(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext();
        $mediaFileService = new DummyMediaFileService();

        $categoryConverter = new Shopware55CategoryConverter($this->mappingService, $mediaFileService, $this->loggingService);
        $categoryConverter->convert($categoryData[1], $context, $this->categoryMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['category'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Translation-Entity could not be converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testCreateConfiguratorOptionTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->createNewUuid(
            $this->migrationContext->getConnection()->getId(),
            DefaultEntities::PROPERTY_GROUP_OPTION . '_option',
            '11',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['configuratoroption'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupOptionDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('My test option', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreateConfiguratorOptionGroupTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->createNewUuid(
            $this->migrationContext->getConnection()->getId(),
            DefaultEntities::PROPERTY_GROUP . '_option',
            '5',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['configuratorgroup'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('My test group', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreatePropertyValueTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->createNewUuid(
            $this->migrationContext->getConnection()->getId(),
            DefaultEntities::PROPERTY_GROUP_OPTION . '_property',
            '31',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['propertyvalue'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupOptionDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('gold', $converted['translations'][$defaultLanguage]['name']);
    }

    public function testCreatePropertyOptionTranslation(): void
    {
        $context = Context::createDefaultContext();
        $defaultLanguage = DummyMappingService::DEFAULT_LANGUAGE_UUID;
        $this->mappingService->createNewUuid(
            $this->migrationContext->getConnection()->getId(),
            DefaultEntities::PROPERTY_GROUP . '_property',
            '8',
            $context
        );

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['propertyoption'];
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);

        static::assertSame(PropertyGroupDefinition::class, $converted['entityDefinitionClass']);
        static::assertSame('Size', $converted['translations'][$defaultLanguage]['optionName']);
    }
}
