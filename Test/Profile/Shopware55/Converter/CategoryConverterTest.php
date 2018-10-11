<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class CategoryConverterTest extends TestCase
{
    /**
     * @var CategoryConverter
     */
    private $categoryConverter;

    protected function setUp()
    {
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->categoryConverter = new CategoryConverter($mappingService, $converterHelperService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->categoryConverter->supports();

        static::assertSame(CategoryDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->categoryConverter->convert($categoryData[0], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayHasKey(Defaults::LANGUAGE, $converted['translations']);
    }

    public function testConvertWithParent(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $this->categoryConverter->convert($categoryData[0], $context, Defaults::CATALOG);
        $convertResult = $this->categoryConverter->convert($categoryData[3], $context, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('parentId', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayHasKey(Defaults::LANGUAGE, $converted['translations']);
    }

    public function testConvertWithParentButParentNotConverted(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $this->expectException(ParentEntityForChildNotFoundException::class);
        $this->categoryConverter->convert($categoryData[4], $context, Defaults::CATALOG);
    }

    public function testConvertWithoutLocale(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $categoryData = $categoryData[0];
        unset($categoryData['_locale']);

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->categoryConverter->convert($categoryData, $context, Defaults::CATALOG);

        static::assertNull($convertResult->getConverted());
    }
}
