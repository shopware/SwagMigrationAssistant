<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class MediaFolderConverterTest extends TestCase
{
    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55MediaFolderConverter
     */
    private $converter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55MediaFolderConverter(new DummyMappingService(), $this->loggingService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new MediaFolderDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $mediaFolderData = require __DIR__ . '/../../../_fixtures/media_folder_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($mediaFolderData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);

        static::assertSame($mediaFolderData[0]['name'], $converted['name']);
        static::assertTrue($converted['configuration']['createThumbnails']);
        static::assertSame(90, $converted['configuration']['thumbnailQuality']);

        static::assertSame(200, $converted['configuration']['mediaThumbnailSizes'][0]['width']);
        static::assertSame(200, $converted['configuration']['mediaThumbnailSizes'][0]['height']);

        static::assertSame(600, $converted['configuration']['mediaThumbnailSizes'][1]['width']);
        static::assertSame(600, $converted['configuration']['mediaThumbnailSizes'][1]['height']);

        static::assertSame(1280, $converted['configuration']['mediaThumbnailSizes'][2]['width']);
        static::assertSame(1280, $converted['configuration']['mediaThumbnailSizes'][2]['height']);
    }

    public function testConvertStructure(): void
    {
        $mediaFolderData = require __DIR__ . '/../../../_fixtures/media_folder_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($mediaFolderData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        $lastId = $converted['id'];

        static::assertSame($mediaFolderData[0]['name'], $converted['name']);

        $convertResult = $this->converter->convert($mediaFolderData[1], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertSame($mediaFolderData[1]['name'], $converted['name']);
        static::assertSame($lastId, $converted['parentId']);
        $lastId = $converted['id'];

        $convertResult = $this->converter->convert($mediaFolderData[2], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertSame($mediaFolderData[2]['name'], $converted['name']);
        static::assertSame($lastId, $converted['parentId']);
    }
}
