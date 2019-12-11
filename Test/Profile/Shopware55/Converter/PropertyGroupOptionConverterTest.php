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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

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
     * @var Shopware55PropertyGroupOptionConverter
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
     * @var Shopware55ProductConverter
     */
    private $productConverter;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $mediaFileService = new DummyMediaFileService();

        $this->propertyGroupOptionConverter = new Shopware55PropertyGroupOptionConverter(
            $this->mappingService,
            $this->loggingService,
            $mediaFileService
        );

        $this->productConverter = new Shopware55ProductConverter(
            $this->mappingService,
            $this->loggingService,
            $mediaFileService
        );

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
            new PropertyGroupOptionDataSet(),
            0,
            250
        );

        $this->mappingService->getOrCreateMapping($this->connection->getId(), DefaultEntities::CURRENCY, 'EUR', Context::createDefaultContext(), null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->propertyGroupOptionConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $propertyData = require __DIR__ . '/../../../_fixtures/property_group_option_data.php';

        $convertResult = $this->propertyGroupOptionConverter->convert($propertyData[0], $this->context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
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
