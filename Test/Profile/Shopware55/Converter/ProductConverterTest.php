<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
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

    protected function setUp()
    {
        $this->mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->productConverter = new ProductConverter($this->mappingService, $converterHelperService);
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
        $convertResult = $this->productConverter->convert($productData[0], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][Defaults::LANGUAGE]['name']
        );
        static::assertSame([], $converted['categories']);
    }

    public function testConvertWithCategory(): void
    {
        $converterHelperService = new ConverterHelperService();
        $categoryConverter = new CategoryConverter($this->mappingService, $converterHelperService);
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $categoryConverter->convert($categoryData[1], $context, Defaults::CATALOG);
        $categoryConverter->convert($categoryData[7], $context, Defaults::CATALOG);

        $convertResult = $this->productConverter->convert($productData[0], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][Defaults::LANGUAGE]['name']
        );
        static::assertArrayHasKey('id', $converted['categories'][0]);
    }

    public function testConvertMainProduct(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->productConverter->convert($productData[1], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame($converted['translations'][Defaults::LANGUAGE]['name'], $converted['children'][0]['translations'][Defaults::LANGUAGE]['name']);
        static::assertSame($converted['id'], $converted['children'][0]['parentId']);
        static::assertSame([], $converted['categories']);
    }

    public function testConvertVariantProduct(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResultContainer = $this->productConverter->convert($productData[1], $context, Defaults::CATALOG);
        $convertResult = $this->productConverter->convert($productData[15], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();
        $convertedContainer = $convertResultContainer->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertSame($convertedContainer['id'], $converted['parentId']);
    }

    public function testConvertVariantProductWithoutParent(): void
    {
        static::markTestSkipped('Remove when variant support is implemented again');
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $this->expectException(ParentEntityForChildNotFoundException::class);
        $this->expectExceptionMessage('Parent entity for "product: SW10007.1" child not found');
        $this->productConverter->convert($productData[15], $context, Defaults::CATALOG);
    }

    public function testConvertWithInvalidAsset(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $productData = $productData[0];
        unset($productData['assets'][0]['media']['id']);

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->productConverter->convert($productData, $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayNotHasKey('cover', $converted);
        static::assertArrayNotHasKey('media', $converted);
    }
}
