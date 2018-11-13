<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

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

    protected function setUp()
    {
        $mediaFileService = new DummyMediaFileService();
        $this->mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->loggingService = new DummyLoggingService();
        $this->productConverter = new ProductConverter($this->mappingService, $converterHelperService, $mediaFileService, $this->loggingService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->productConverter->supports();

        static::assertSame(ProductDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertSame([], $converted['categories']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithCategory(): void
    {
        $converterHelperService = new ConverterHelperService();
        $categoryConverter = new CategoryConverter($this->mappingService, $converterHelperService, $this->loggingService);
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $profileUuid = Uuid::uuid4()->getHex();
        $categoryConverter->convert($categoryData[1], $context, Uuid::uuid4()->getHex(), $profileUuid, Defaults::CATALOG);
        $categoryConverter->convert($categoryData[7], $context, Uuid::uuid4()->getHex(), $profileUuid, Defaults::CATALOG);

        $convertResult = $this->productConverter->convert($productData[0], $context, Uuid::uuid4()->getHex(), $profileUuid, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertArrayHasKey('id', $converted['categories'][0]);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertMainProduct(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->productConverter->convert($productData[1], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame($converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name'], $converted['children'][0]['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
        static::assertSame($converted['id'], $converted['children'][0]['parentId']);
        static::assertSame([], $converted['categories']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertVariantProduct(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $profileId = Uuid::uuid4()->getHex();
        $convertResultContainer = $this->productConverter->convert($productData[1], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);
        $convertResult = $this->productConverter->convert($productData[15], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $converted = $convertResult->getConverted();
        $convertedContainer = $convertResultContainer->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame($convertedContainer['id'], $converted['parentId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertVariantProductWithoutParent(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $this->expectException(ParentEntityForChildNotFoundException::class);
        $this->expectExceptionMessage('Parent entity for "product: SW10007.1" child not found');
        $this->productConverter->convert($productData[15], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithInvalidAsset(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $productData = $productData[0];
        unset($productData['assets'][0]['media']['id']);

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->productConverter->convert($productData, $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayNotHasKey('cover', $converted);
        static::assertArrayNotHasKey('media', $converted);

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Product-Media could not converted.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
