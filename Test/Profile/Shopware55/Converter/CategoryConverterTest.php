<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Converter\CategoryConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
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

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string
     */
    private $profileId;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    protected function setUp()
    {
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->loggingService = new DummyLoggingService();
        $this->categoryConverter = new CategoryConverter($mappingService, $converterHelperService, $this->loggingService);

        $this->runId = Uuid::uuid4()->getHex();
        $this->profileId = Uuid::uuid4()->getHex();

        $this->migrationContext = new MigrationContext(
            $this->runId,
            $this->profileId,
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250,
            Defaults::CATALOG
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->categoryConverter->supports(Shopware55Profile::PROFILE_NAME, CategoryDefinition::getEntityName());

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->categoryConverter->convert($categoryData[0], $context, $this->migrationContext);

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
        $this->categoryConverter->convert($categoryData[0], $context, $this->migrationContext);
        $convertResult = $this->categoryConverter->convert($categoryData[3], $context, $this->migrationContext);

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
        $this->categoryConverter->convert($categoryData[4], $context, $this->migrationContext);
    }

    public function testConvertWithoutLocale(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $categoryData = $categoryData[0];
        unset($categoryData['_locale']);

        $context = Context::createDefaultContext();
        $convertResult = $this->categoryConverter->convert($categoryData, $context, $this->migrationContext);
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = 'Category-Entity could not converted cause of empty locale.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
    }
}
