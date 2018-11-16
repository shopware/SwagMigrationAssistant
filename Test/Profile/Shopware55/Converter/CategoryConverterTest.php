<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class CategoryConverterTest extends TestCase
{
    /**
     * @var CategoryConverter
     */
    private $categoryConverter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    protected function setUp()
    {
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->loggingService = new DummyLoggingService();
        $this->categoryConverter = new CategoryConverter($mappingService, $converterHelperService, $this->loggingService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->categoryConverter->supports();

        static::assertSame(CategoryDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->categoryConverter->convert($categoryData[0], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
    }

    public function testConvertWithParent(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();
        $profileId = Uuid::uuid4()->getHex();
        $this->categoryConverter->convert($categoryData[0], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);
        $convertResult = $this->categoryConverter->convert($categoryData[3], $context, Uuid::uuid4()->getHex(), $profileId, Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('parentId', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
    }

    public function testConvertWithParentButParentNotConverted(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();
        $this->expectException(ParentEntityForChildNotFoundException::class);
        $this->categoryConverter->convert($categoryData[4], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);
    }

    public function testConvertWithoutLocale(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $categoryData = $categoryData[0];
        unset($categoryData['_locale']);

        $context = Context::createDefaultContext();
        $convertResult = $this->categoryConverter->convert($categoryData, $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Entity could not converted cause of empty locale.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
