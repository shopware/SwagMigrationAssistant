<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DefaultCmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LowestRootCategoryLookup;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use Symfony\Component\HttpFoundation\Response;

#[Package('services-settings')]
class CategoryConverterTest extends TestCase
{
    use KernelTestBehaviour;

    private Shopware55CategoryConverter $categoryConverter;

    private DummyLoggingService $loggingService;

    private MigrationContextInterface $migrationContext;

    protected function setUp(): void
    {
        $mediaFileService = new DummyMediaFileService();
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->categoryConverter = new Shopware55CategoryConverter(
            $mappingService,
            $this->loggingService,
            $mediaFileService,
            $this->getContainer()->get(LowestRootCategoryLookup::class),
            $this->getContainer()->get(DefaultCmsPageLookup::class),
            $this->getContainer()->get(LanguageLookup::class),
        );

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new CategoryDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->categoryConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();

        $locale = new LocaleEntity();
        $locale->setCode('en-GB');
        $language = new LanguageEntity();
        $language->setId(DummyMappingService::DEFAULT_LANGUAGE_UUID);
        $language->setLocale($locale);

        $languageLookup = $this->createMock(LanguageLookup::class);
        $languageLookup->method('get')->willReturn(DummyMappingService::DEFAULT_LANGUAGE_UUID);
        $languageLookup->method('getDefaultLanguageEntity')->willReturn($language);

        $categoryConverter = new Shopware55CategoryConverter(
            new DummyMappingService(),
            $this->loggingService,
            new DummyMediaFileService(),
            $this->getContainer()->get(LowestRootCategoryLookup::class),
            $this->getContainer()->get(DefaultCmsPageLookup::class),
            $languageLookup
        );

        $convertResult = $categoryConverter->convert($categoryData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertSame($categoryData[0]['asset']['name'], $converted['media']['title']);
        static::assertSame($categoryData[0]['asset']['description'], $converted['media']['alt']);
    }

    public function testConvertWithParent(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $locale = new LocaleEntity();
        $locale->setCode('en-GB');
        $language = new LanguageEntity();
        $language->setLocale($locale);

        $languageLookup = $this->createMock(LanguageLookup::class);
        $languageLookup->method('get')->willReturn(DummyMappingService::DEFAULT_LANGUAGE_UUID);
        $languageLookup->method('getDefaultLanguageEntity')->willReturn($language);
        $categoryConverter = new Shopware55CategoryConverter(
            new DummyMappingService(),
            $this->loggingService,
            new DummyMediaFileService(),
            $this->getContainer()->get(LowestRootCategoryLookup::class),
            $this->getContainer()->get(DefaultCmsPageLookup::class),
//            $this->getContainer()->get(LanguageLookup::class),
            $languageLookup
        );

        $context = Context::createDefaultContext();
        $categoryConverter->convert($categoryData[0], $context, $this->migrationContext);
        $convertResult = $categoryConverter->convert($categoryData[3], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('parentId', $converted);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
    }

    public function testConvertWithParentButParentNotConverted(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();

        try {
            $this->categoryConverter->convert($categoryData[4], $context, $this->migrationContext);
        } catch (\Exception $e) {
            static::assertInstanceOf(MigrationException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame(MigrationException::PARENT_ENTITY_NOT_FOUND, $e->getErrorCode());
            static::assertSame('Parent entity for "category: 9" child not found.', $e->getMessage());
        }
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
        $title = 'The category entity has one or more empty necessary fields';
        static::assertSame($title, $logs[0]['title']);
        static::assertCount(1, $logs);
    }

    public function testConvertWithExternalLink(): void
    {
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->categoryConverter->convert($categoryData[8], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(CategoryDefinition::TYPE_LINK, $converted['type']);
        static::assertSame('www.shopware.com', $converted['externalLink']);
    }
}
