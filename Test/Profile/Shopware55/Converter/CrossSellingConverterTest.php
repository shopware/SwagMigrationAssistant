<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CrossSellingDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class CrossSellingConverterTest extends TestCase
{
    /**
     * @var Shopware55CrossSellingConverter
     */
    private $crossSellingConverter;

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

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var array
     */
    private $product0;

    /**
     * @var array
     */
    private $product1;

    /**
     * @var array
     */
    private $product2;

    /**
     * @var array
     */
    private $product3;

    protected function setUp(): void
    {
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->crossSellingConverter = new Shopware55CrossSellingConverter($this->mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CrossSellingDataSet(),
            0,
            250
        );

        $this->product0 = $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::PRODUCT_CONTAINER, '123', Context::createDefaultContext(), null, [], Uuid::randomHex());
        $this->product1 = $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::PRODUCT_CONTAINER, '117', Context::createDefaultContext(), null, [], Uuid::randomHex());
        $this->product2 = $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::PRODUCT_CONTAINER, '114', Context::createDefaultContext(), null, [], Uuid::randomHex());
        $this->product3 = $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::PRODUCT_CONTAINER, '113', Context::createDefaultContext(), null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->crossSellingConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/cross_selling_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->crossSellingConverter->convert($data[0], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('productList', $converted['type']);
        static::assertSame($this->product0['entityUuid'], $converted['productId']);
        static::assertTrue($converted['active']);
        static::assertArrayHasKey('assignedProducts', $converted);
        static::assertCount(1, $converted['assignedProducts']);
        static::assertSame($this->product1['entityUuid'], $converted['assignedProducts'][0]['productId']);
        static::assertNotNull($convertResult->getMappingUuid());
    }

    public function testConvertMultipleItems(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/cross_selling_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->crossSellingConverter->convert($data[1], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('productList', $converted['type']);
        static::assertSame($this->product1['entityUuid'], $converted['productId']);
        static::assertTrue($converted['active']);
        static::assertArrayHasKey('assignedProducts', $converted);
        static::assertCount(1, $converted['assignedProducts']);
        static::assertSame($this->product2['entityUuid'], $converted['assignedProducts'][0]['productId']);
        static::assertNotNull($convertResult->getMappingUuid());

        $convertResult = $this->crossSellingConverter->convert($data[2], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('productList', $converted['type']);
        static::assertSame($this->product1['entityUuid'], $converted['productId']);
        static::assertTrue($converted['active']);
        static::assertArrayHasKey('assignedProducts', $converted);
        static::assertCount(1, $converted['assignedProducts']);
        static::assertSame($this->product3['entityUuid'], $converted['assignedProducts'][0]['productId']);
        static::assertNotNull($convertResult->getMappingUuid());

        $convertResult = $this->crossSellingConverter->convert($data[3], $context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame('productList', $converted['type']);
        static::assertSame($this->product1['entityUuid'], $converted['productId']);
        static::assertTrue($converted['active']);
        static::assertArrayHasKey('assignedProducts', $converted);
        static::assertCount(1, $converted['assignedProducts']);
        static::assertSame($this->product0['entityUuid'], $converted['assignedProducts'][0]['productId']);
        static::assertNotNull($convertResult->getMappingUuid());
    }

    public function testConvertWithoutMapping(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/cross_selling_data.php';
        $product = $data[0];
        $product['articleID'] = '99';

        $context = Context::createDefaultContext();
        $convertResult = $this->crossSellingConverter->convert($product, $context, $this->migrationContext);

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_PRODUCT', $logs[0]['code']);
        static::assertSame('99', $logs[0]['parameters']['sourceId']);

        $this->loggingService->resetLogging();
        $data[0]['relatedarticle'] = '80';
        $convertResult = $this->crossSellingConverter->convert($data[0], $context, $this->migrationContext);

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_PRODUCT', $logs[0]['code']);
        static::assertSame('80', $logs[0]['parameters']['sourceId']);
    }
}
