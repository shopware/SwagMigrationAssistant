<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class TranslationConverterTest extends TestCase
{
    /**
     * @var TranslationConverter
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

    protected function setUp()
    {
        $this->mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->loggingService = new DummyLoggingService();
        $this->translationConverter = new TranslationConverter($this->mappingService, $converterHelperService, $this->loggingService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->translationConverter->supports();

        static::assertSame('translation', $supportsDefinition);
    }

    public function testConvertUnknownTranslationType(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['invalid'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Translation of object type "invalid" could not converted.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertProductTranslation(): void
    {
        static::markTestSkipped('Reimplement when product translation works again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['product'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        static::assertNull($convertResult->getUnmapped());
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertManufacturerTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConvertResult = $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['manufacturer'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedProduct['manufacturer']['id'], $converted['productManufacturerId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertManufacturerTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['manufacturer'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());
    }

    public function testConvertManufacturerTranslationWithInvalidTranslationObject(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['manufacturer'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Manufacturer-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertManufacturerTranslationWithUnhandledTranslations(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['manufacturer'];
        $objectData = unserialize($translationData['objectdata'], ['allowed_classes' => false]);
        $objectData['foo'] = 'bar';
        $translationData['objectdata'] = serialize($objectData);
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Manufacturer-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConvertResult = $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['unit'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();
        $convertedProduct = $productConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedProduct['unit']['id'], $converted['unitId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertUnitTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['unit'], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Mapping of "unit" is missing, but it is a required association for "translation". Import "product" first.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslationWithInvalidTranslationObject(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileId = Uuid::uuid4()->getHex();
        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['unit'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Unit-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslationWithUnhandledTranslations(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileId = Uuid::uuid4()->getHex();
        $productConverter = new ProductConverter($this->mappingService, new ConverterHelperService(), new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['unit'];
        $objectData = unserialize($translationData['objectdata'], ['allowed_classes' => false]);
        $objectData[9]['foo'] = 'bar';
        $translationData['objectdata'] = serialize($objectData);
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Unit-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslation(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileId = Uuid::uuid4()->getHex();
        $categoryConverter = new CategoryConverter($this->mappingService, new ConverterHelperService(), $this->loggingService);
        $categoryConvertResult = $categoryConverter->convert($categoryData[1], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['category'], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $converted = $convertResult->getConverted();
        $convertedCategory = $categoryConvertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame($convertedCategory['id'], $converted['categoryId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCategoryTranslationWithoutParent(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['category'], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Mapping of "category" is missing, but it is a required association for "translation". Import "category" first.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslationWithInvalidTranslationObject(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileId = Uuid::uuid4()->getHex();
        $categoryConverter = new CategoryConverter($this->mappingService, new ConverterHelperService(), $this->loggingService);
        $categoryConverter->convert($categoryData[1], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['category'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslationWithUnhandledTranslations(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileId = Uuid::uuid4()->getHex();
        $categoryConverter = new CategoryConverter($this->mappingService, new ConverterHelperService(), $this->loggingService);
        $categoryConverter->convert($categoryData[1], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['category'];
        $objectData = unserialize($translationData['objectdata'], ['allowed_classes' => false]);
        $objectData['foo'] = 'bar';
        $translationData['objectdata'] = serialize($objectData);
        $convertResult = $this->translationConverter->convert($translationData, $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
