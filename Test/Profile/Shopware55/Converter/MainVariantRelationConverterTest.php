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
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MainVariantRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class MainVariantRelationConverterTest extends TestCase
{
    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55MainVariantRelationConverter
     */
    private $converter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var array
     */
    private $productContainer1;

    /**
     * @var array
     */
    private $productContainer2;

    /**
     * @var array
     */
    private $productVariant1;

    /**
     * @var array
     */
    private $productVariant2;

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new DummyMappingService();
        $this->converter = new Shopware55MainVariantRelationConverter($this->mappingService, $this->loggingService);

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new MainVariantRelationDataSet(),
            0,
            250
        );

        $this->productContainer1 = $this->mappingService->getOrCreateMapping(
            $connection->getId(),
            DefaultEntities::PRODUCT_CONTAINER,
            '223',
            $context
        );

        $this->productContainer2 = $this->mappingService->getOrCreateMapping(
            $connection->getId(),
            DefaultEntities::PRODUCT_CONTAINER,
            '273',
            $context
        );

        $this->productVariant1 = $this->mappingService->getOrCreateMapping(
            $connection->getId(),
            DefaultEntities::PRODUCT,
            'SW100718.1',
            $context
        );

        $this->productVariant2 = $this->mappingService->getOrCreateMapping(
            $connection->getId(),
            DefaultEntities::PRODUCT,
            'SW10002',
            $context
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->converter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/main_variant_relation.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->converter->convert($data[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertSame($this->productContainer1['entityUuid'], $converted['id']);
        static::assertSame($this->productVariant1['entityUuid'], $converted['mainVariantId']);

        $convertResult = $this->converter->convert($data[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertSame($this->productContainer2['entityUuid'], $converted['id']);
        static::assertSame($this->productVariant2['entityUuid'], $converted['mainVariantId']);
    }

    public function testConvertWithoutMapping(): void
    {
        $data = require __DIR__ . '/../../../_fixtures/main_variant_relation.php';
        $context = Context::createDefaultContext();
        $raw1 = $data[0];
        $raw2 = $data[0];

        $raw1['id'] = 'invalid-id';
        $convertResult = $this->converter->convert($raw1, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        $parameters = [
            'missingEntity' => 'product_container',
            'requiredFor' => 'main_variant_relation',
            'sourceId' => 'invalid-id',
        ];
        $logs = $this->loggingService->getLoggingArray();
        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($converted);
        static::assertCount(1, $logs);
        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_PRODUCT_CONTAINER', $logs[0]['code']);
        static::assertSame($parameters, $logs[0]['parameters']);

        $this->loggingService->resetLogging();
        $raw2['ordernumber'] = 'invalid-ordernumber';
        $convertResult = $this->converter->convert($raw2, $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        $parameters = [
            'missingEntity' => 'product',
            'requiredFor' => 'main_variant_relation',
            'sourceId' => 'invalid-ordernumber',
        ];
        $logs = $this->loggingService->getLoggingArray();
        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($converted);
        static::assertCount(1, $logs);
        static::assertSame('SWAG_MIGRATION__SHOPWARE_ASSOCIATION_REQUIRED_MISSING_PRODUCT', $logs[0]['code']);
        static::assertSame($parameters, $logs[0]['parameters']);
    }
}
