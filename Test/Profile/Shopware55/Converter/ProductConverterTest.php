<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

class ProductConverterTest extends TestCase
{
    /**
     * @var Shopware55ProductConverter
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
        $this->productConverter = new Shopware55ProductConverter($this->mappingService, $this->loggingService, $mediaFileService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ProductDataSet(),
            0,
            250
        );

        $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::CURRENCY, 'EUR', Context::createDefaultContext(), null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->productConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertNotNull($converted['categories']);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame(
            'Hauptartikel mit Kennzeichnung Versandkostenfrei und Hervorhebung',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertSame([], $converted['categories']);
        static::assertSame($productData[0]['assets'][0]['description'], $converted['media'][0]['media']['alt']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutAttributes(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $productData['0']['attributes'] = [];

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertNull($converted['customFields']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithCategory(): void
    {
        $mediaFileService = new DummyMediaFileService();
        $categoryConverter = new Shopware55CategoryConverter($this->mappingService, $this->loggingService, $mediaFileService);
        $categoryData = require __DIR__ . '/../../../_fixtures/category_data.php';
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $context = Context::createDefaultContext();

        $categoryConverter->convert($categoryData[1], $context, $this->migrationContext);
        $categoryConverter->convert($categoryData[7], $context, $this->migrationContext);

        $convertResult = $this->productConverter->convert($productData[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertNotNull($converted['categories']);
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
        static::assertNotNull($converted);
        static::assertNotNull($converted['children']);
        static::assertNotNull($converted['translations']);
        static::assertNotNull($converted['categories']);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertSame($converted['id'], $converted['children'][0]['parentId']);
        static::assertSame([], $converted['categories']);
        static::assertArrayNotHasKey('options', $converted);
        static::assertArrayHasKey('options', $converted['children'][0]);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertVariantProduct(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResultContainer = $this->productConverter->convert($productData[1], $context, $this->migrationContext);
        $convertResult = $this->productConverter->convert($productData[15], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        $convertedContainer = $convertResultContainer->getConverted();
        static::assertNotNull($convertedContainer);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertArrayHasKey('options', $converted);

        static::assertArrayHasKey('media', $converted);
        static::assertCount(2, $converted['media']);
        static::assertSame('hemd', $converted['media'][0]['media']['title']);
        static::assertSame(2, $converted['media'][0]['position']);
        static::assertSame('hemd1', $converted['media'][1]['media']['title']);
        static::assertSame(1, $converted['media'][1]['position']);
        static::assertArrayHasKey('cover', $converted);
        static::assertSame('hemd1', $converted['cover']['media']['title']);

        static::assertSame($convertedContainer['id'], $converted['parentId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());

        static::assertSame('Größe', $converted['options'][0]['group']['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
        static::assertSame('M', $converted['options'][0]['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']);
    }

    public function testConvertVariantProductWithSpecialCoverImageConstellation(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResultContainer = $this->productConverter->convert($productData[1], $context, $this->migrationContext);

        $preparedProductData = $productData[15];
        $preparedProductData['assets'][0]['main'] = '1';

        $convertResult = $this->productConverter->convert($preparedProductData, $context, $this->migrationContext);

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        $convertedContainer = $convertResultContainer->getConverted();
        static::assertNotNull($convertedContainer);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);

        static::assertArrayHasKey('media', $converted);
        static::assertCount(2, $converted['media']);
        static::assertSame('hemd', $converted['media'][0]['media']['title']);
        static::assertSame(2, $converted['media'][0]['position']);
        static::assertSame('hemd1', $converted['media'][1]['media']['title']);
        static::assertSame(1, $converted['media'][1]['position']);
        static::assertArrayHasKey('cover', $converted);
        static::assertSame('hemd', $converted['cover']['media']['title']);
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
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('manufacturer', $converted);
        static::assertArrayHasKey('price', $converted);
        static::assertArrayNotHasKey('cover', $converted);
        static::assertArrayNotHasKey('media', $converted);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_CANNOT_CONVERT_CHILD_PRODUCT_MEDIA_ENTITY');
        static::assertSame($logs[0]['parameters']['parentSourceId'], 'SW10006');
        static::assertSame($logs[0]['parameters']['entity'], 'product_media');
    }

    public function testConvertDeliveryTime(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $productData = $productData[0];
        $productData['detail']['shippingtime'] = '10';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertSame(10, $converted['deliveryTime']['min']);
        static::assertSame(0, $converted['deliveryTime']['max']);
        static::assertSame('day', $converted['deliveryTime']['unit']);
        static::assertSame('10 days', $converted['deliveryTime']['name']);

        $productData['detail']['shippingtime'] = '10-20';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertSame(10, $converted['deliveryTime']['min']);
        static::assertSame(20, $converted['deliveryTime']['max']);
        static::assertSame('day', $converted['deliveryTime']['unit']);
        static::assertSame('10-20 days', $converted['deliveryTime']['name']);

        $productData['detail']['shippingtime'] = '10-20 weeks';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData, $context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertSame(10, $converted['deliveryTime']['min']);
        static::assertSame(20, $converted['deliveryTime']['max']);
        static::assertSame('day', $converted['deliveryTime']['unit']);
        static::assertSame('10-20 days', $converted['deliveryTime']['name']);
    }

    public function testConvertWithPseudoPrices(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->productConverter->convert($productData[1], $context, $this->migrationContext);
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('price', $converted);
        static::assertArrayHasKey('listPrice', $converted['price'][0]);
        static::assertSame((float) $productData[1]['prices'][0]['pseudoprice'], $converted['price'][0]['listPrice']['net']);
    }
}
