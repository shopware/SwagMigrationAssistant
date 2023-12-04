<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;

#[Package('services-settings')]
class PropertyGroupOptionConverterTest extends TestCase
{
    private DummyMappingService $mappingService;

    private DummyLoggingService $loggingService;

    private Shopware55PropertyGroupOptionConverter $propertyGroupOptionConverter;

    private Context $context;

    private SwagMigrationConnectionEntity $connection;

    private MigrationContext $migrationContext;

    private Shopware55ProductConverter $productConverter;

    private Shopware55ProductOptionRelationConverter $optionRelationConverter;

    private Shopware55ProductPropertyRelationConverter $propertyRelationConverter;

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

        $this->optionRelationConverter = new Shopware55ProductOptionRelationConverter(
            $this->mappingService,
            $this->loggingService
        );

        $this->propertyRelationConverter = new Shopware55ProductPropertyRelationConverter(
            $this->mappingService,
            $this->loggingService
        );

        $runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $runId,
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

        $convertResult = $this->propertyGroupOptionConverter->convert($propertyData[10], $this->context, $this->migrationContext);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('translations', $converted);
        static::assertSame(
            '16%',
            $converted['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertArrayHasKey('translations', $converted['group']);
        static::assertSame(
            'Alkoholgehalt',
            $converted['group']['translations'][DummyMappingService::DEFAULT_LANGUAGE_UUID]['name']
        );
        static::assertCount(0, $this->loggingService->getLoggingArray());
        static::assertSame($propertyData[10]['media']['name'], $converted['media']['title']);
        static::assertSame($propertyData[10]['media']['description'], $converted['media']['alt']);
    }

    public function testConvertWithPropertiesAndProductConfigurators(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $propertyData = require __DIR__ . '/../../../_fixtures/property_group_option_data.php';
        $optionRelationData = require __DIR__ . '/../../../_fixtures/product_option_relation.php';
        $propertyRelationData = require __DIR__ . '/../../../_fixtures/product_property_relation.php';

        $mainProduct = $this->productConverter->convert($productData[5], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[22], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[23], $this->context, $this->migrationContext);
        $convertedMainProduct = $mainProduct->getConverted();
        static::assertNotNull($convertedMainProduct);

        $property0 = $this->propertyGroupOptionConverter->convert($propertyData[4], $this->context, $this->migrationContext);
        $property1 = $this->propertyGroupOptionConverter->convert($propertyData[2], $this->context, $this->migrationContext);
        $property2 = $this->propertyGroupOptionConverter->convert($propertyData[3], $this->context, $this->migrationContext);

        $iterator = 0;
        foreach ($optionRelationData as &$relation) {
            $relation['productId'] = $productData[5]['detail']['articleID'];

            $convertedStruct = $this->optionRelationConverter->convert($relation, $this->context, $this->migrationContext);
            $converted = $convertedStruct->getConverted();

            static::assertNotNull($converted);
            static::assertSame($convertedMainProduct['id'], $converted['id']);
            $property = ${'property' . $iterator};
            static::assertInstanceOf(ConvertStruct::class, $property);
            $firstConverted = $property->getConverted();
            static::assertIsArray($firstConverted);
            static::assertSame($firstConverted['id'], $converted['configuratorSettings'][0]['optionId']);

            ++$iterator;
        }

        $iterator = 0;
        foreach ($propertyRelationData as &$relation) {
            $relation['productId'] = $productData[5]['detail']['articleID'];

            $convertedStruct = $this->propertyRelationConverter->convert($relation, $this->context, $this->migrationContext);
            $converted = $convertedStruct->getConverted();

            static::assertNotNull($converted);
            static::assertSame($convertedMainProduct['id'], $converted['id']);
            $property = ${'property' . $iterator};
            static::assertInstanceOf(ConvertStruct::class, $property);
            $firstConverted = $property->getConverted();
            static::assertIsArray($firstConverted);
            static::assertSame($firstConverted['id'], $converted['properties'][0]['id']);

            ++$iterator;
        }
    }

    public function testConvertWithPropertiesAndProductConfiguratorsAndOldIdentifier(): void
    {
        $productData = require __DIR__ . '/../../../_fixtures/product_data.php';
        $propertyData = require __DIR__ . '/../../../_fixtures/property_group_option_data.php';
        $optionRelationData = require __DIR__ . '/../../../_fixtures/product_option_relation.php';
        $propertyRelationData = require __DIR__ . '/../../../_fixtures/product_property_relation.php';

        $mainProduct = $this->productConverter->convert($productData[5], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[22], $this->context, $this->migrationContext);
        $this->productConverter->convert($productData[23], $this->context, $this->migrationContext);
        $convertedMainProduct = $mainProduct->getConverted();
        static::assertNotNull($convertedMainProduct);

        $property0 = $this->propertyGroupOptionConverter->convert($propertyData[4], $this->context, $this->migrationContext);
        $property1 = $this->propertyGroupOptionConverter->convert($propertyData[2], $this->context, $this->migrationContext);
        $property2 = $this->propertyGroupOptionConverter->convert($propertyData[3], $this->context, $this->migrationContext);

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::PRODUCT_PROPERTY,
            $propertyData[4]['id'] . '_' . $convertedMainProduct['id'],
            $this->context
        );
        $oldMappingId0 = $mapping['entityUuid'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::PRODUCT_PROPERTY,
            $propertyData[2]['id'] . '_' . $convertedMainProduct['id'],
            $this->context
        );
        $oldMappingId1 = $mapping['entityUuid'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::PRODUCT_PROPERTY,
            $propertyData[3]['id'] . '_' . $convertedMainProduct['id'],
            $this->context
        );
        $oldMappingId2 = $mapping['entityUuid'];

        $iterator = 0;
        foreach ($optionRelationData as &$relation) {
            $relation['productId'] = $productData[5]['detail']['articleID'];

            $convertedStruct = $this->optionRelationConverter->convert($relation, $this->context, $this->migrationContext);
            $converted = $convertedStruct->getConverted();

            static::assertNotNull($converted);
            static::assertSame($convertedMainProduct['id'], $converted['id']);
            $property = ${'property' . $iterator};
            static::assertInstanceOf(ConvertStruct::class, $property);
            $firstConverted = $property->getConverted();
            static::assertIsArray($firstConverted);
            static::assertSame($firstConverted['id'], $converted['configuratorSettings'][0]['optionId']);
            static::assertSame(${'oldMappingId' . $iterator}, $converted['configuratorSettings'][0]['id']);

            ++$iterator;
        }

        $iterator = 0;
        foreach ($propertyRelationData as &$relation) {
            $relation['productId'] = $productData[5]['detail']['articleID'];

            $convertedStruct = $this->propertyRelationConverter->convert($relation, $this->context, $this->migrationContext);
            $converted = $convertedStruct->getConverted();

            static::assertNotNull($converted);
            static::assertSame($convertedMainProduct['id'], $converted['id']);
            $property = ${'property' . $iterator};
            static::assertInstanceOf(ConvertStruct::class, $property);
            $firstConverted = $property->getConverted();
            static::assertIsArray($firstConverted);
            static::assertSame($firstConverted['id'], $converted['properties'][0]['id']);

            ++$iterator;
        }
    }
}
