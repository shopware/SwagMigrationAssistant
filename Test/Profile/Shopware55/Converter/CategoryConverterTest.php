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
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use Symfony\Component\HttpFoundation\Response;

class CategoryConverterTest extends TestCase
{
    /**
     * @var Shopware55CategoryConverter
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
    private $connection;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    protected function setUp(): void
    {
        $mediaFileService = new DummyMediaFileService();
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->categoryConverter = new Shopware55CategoryConverter($mappingService, $this->loggingService, $mediaFileService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
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
        $convertResult = $this->categoryConverter->convert($categoryData[0], $context, $this->migrationContext);

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

        $context = Context::createDefaultContext();
        $this->categoryConverter->convert($categoryData[0], $context, $this->migrationContext);
        $convertResult = $this->categoryConverter->convert($categoryData[3], $context, $this->migrationContext);

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
            /* @var ParentEntityForChildNotFoundException $e */
            static::assertInstanceOf(ParentEntityForChildNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
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
