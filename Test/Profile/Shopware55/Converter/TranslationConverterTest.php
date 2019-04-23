<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\TranslationConverter;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;

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
        $this->translationConverter = new TranslationConverter($this->mappingService, $this->loggingService);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $connection->setProfile($profile);
        $connection->setCredentialFields([]);

        $this->runId = Uuid::randomHex();
        $this->profileId = Uuid::randomHex();

        $this->migrationContext = new MigrationContext(
            $connection,
            $this->runId,
            new TranslationDataSet(),
            0,
            250
        );

        $this->productMigrationContext = new MigrationContext(
            $connection,
            $this->runId,
            new ProductDataSet(),
            0,
            250
        );

        $this->categoryMigrationContext = new MigrationContext(
            $connection,
            $this->runId,
            new CategoryDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->translationConverter->supports(Shopware55Profile::PROFILE_NAME, new TranslationDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvertUnknownTranslationType(): void
    {
        $context = Context::createDefaultContext();
        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $convertResult = $this->translationConverter->convert($translationData['invalid'], $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Translation of object type "invalid" could not converted.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertProductTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
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

        $productConverter = new ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
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

        $productConverter = new ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['manufacturer'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Manufacturer-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertUnitTranslation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $productConverter = new ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
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

        $productConverter = new ProductConverter($this->mappingService, new DummyMediaFileService(), $this->loggingService);
        $productConverter->convert($productData[0], $context, $this->productMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['unit'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Unit-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }

    public function testConvertCategoryTranslation(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $context = Context::createDefaultContext();

        $categoryConverter = new CategoryConverter($this->mappingService, $this->loggingService);
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

        $categoryConverter = new CategoryConverter($this->mappingService, $this->loggingService);
        $categoryConverter->convert($categoryData[1], $context, $this->categoryMigrationContext);

        $translationData = require __DIR__ . '/../../../_fixtures/translation_data.php';
        $translationData = $translationData['category'];
        $translationData['objectdata'] = 's:19:"no serialized array";';
        $convertResult = $this->translationConverter->convert($translationData, $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Translation-Entity could not converted cause of invalid unserialized object data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
