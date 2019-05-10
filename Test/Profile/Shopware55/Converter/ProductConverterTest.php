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
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;

class ProductConverterTest extends TestCase
{
    /**
     * @var ProductConverter
     */
    private $productConverter;

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
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $mediaFileService = new DummyMediaFileService();
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->productConverter = new ProductConverter($this->mappingService, $mediaFileService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfile($profile);

        $this->migrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new ProductDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->productConverter->supports(Shopware55Profile::PROFILE_NAME, new ProductDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertSame([], $converted['categories']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithCategory(): void
    {
        $mediaFileService = new DummyMediaFileService();
        $categoryConverter = new CategoryConverter($this->mappingService, $mediaFileService, $this->loggingService);
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $categoryConverter->convert($categoryData[1], $context, $this->migrationContext);
        $categoryConverter->convert($categoryData[7], $context, $this->migrationContext);

        $convertResult = $this->productConverter->convert($productData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertArrayHasKey('id', $converted['categories'][0]);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertMainProduct(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData[1], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame($converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name'], $converted['children'][0]['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
        static::assertSame($converted['id'], $converted['children'][0]['parentId']);
        static::assertSame([], $converted['categories']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertVariantProduct(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResultContainer = $this->productConverter->convert($productData[1], $context, $this->migrationContext);
        $convertResult = $this->productConverter->convert($productData[15], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        $convertedContainer = $convertResultContainer->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertArrayHasKey('options', $converted);
        static::assertSame($convertedContainer['id'], $converted['parentId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());

        static::assertSame('Größe', $converted['options'][0]['group']['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
        static::assertSame('M', $converted['options'][0]['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
    }

    public function testConvertVariantProductWithoutParent(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();

        $this->expectException(ParentEntityForChildNotFoundException::class);
        $this->expectExceptionMessage('Parent entity for "product: SW10007.1" child not found');
        $this->productConverter->convert($productData[15], $context, $this->migrationContext);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithInvalidMedia(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $productData = $productData[0];
        unset($productData['assets'][0]['media']['id']);

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData, $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertArrayNotHasKey('cover', $converted);
        static::assertArrayNotHasKey('media', $converted);

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Product-Media could not be converted.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
