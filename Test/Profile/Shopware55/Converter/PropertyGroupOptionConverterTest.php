<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\PropertyGroupOptionConverter;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;

class PropertyGroupOptionConverterTest extends TestCase
{
    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var PropertyGroupOptionConverter
     */
    private $propertyGroupOptionConverter;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var ProductConverter
     */
    private $productConverter;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $mediaFileService = new DummyMediaFileService();

        $this->propertyGroupOptionConverter = new PropertyGroupOptionConverter(
            $this->mappingService,
            $mediaFileService,
            $this->loggingService
        );

        $this->productConverter = new ProductConverter(
            $this->mappingService,
            $mediaFileService,
            $this->loggingService
        );

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfile($profile);

        $this->migrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new PropertyGroupOptionDataSet(),
            0,
            250
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->propertyGroupOptionConverter->supports(Shopware55Profile::PROFILE_NAME, new PropertyGroupOptionDataSet());

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $propertyData = require __DIR__ . '/../../../_fixtures/property_group_option_data.php';

        $convertResult = $this->propertyGroupOptionConverter->convert($propertyData[0], $this->context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('translations', $converted);
        static::assertSame(
            'Rot',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertArrayHasKey('translations', $converted['group']);
        static::assertSame(
            'Farbe',
            $converted['group']['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithDatasheetsAndProductConfigurators(): void
    {
        $propertyData = require __DIR__ . '/../../../_fixtures/property_group_option_data.php';
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';

        $mainProduct1 = $this->productConverter->convert($productData[5], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[22], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[23], $this->context, $this->migrationContext);

        $mainProduct2 = $this->productConverter->convert($productData[4], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[14], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[18], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[19], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[20], $this->context, $this->migrationContext);

        $convertResult = $this->propertyGroupOptionConverter->convert($propertyData[0], $this->context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('translations', $converted);

        static::assertArrayHasKey('productProperties', $converted);
        static::assertArrayHasKey('productConfiguratorSettings', $converted);

        static::assertSame($converted['productProperties'][0]['id'], $mainProduct1->getConverted()['id']);
        static::assertSame($converted['productConfiguratorSettings'][0]['productId'], $mainProduct1->getConverted()['id']);

        static::assertSame($converted['productProperties'][1]['id'], $mainProduct2->getConverted()['id']);
        static::assertSame($converted['productConfiguratorSettings'][1]['productId'], $mainProduct2->getConverted()['id']);

        static::assertSame(
            'Rot',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertArrayHasKey('translations', $converted['group']);
        static::assertSame(
            'Farbe',
            $converted['group']['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }
}
