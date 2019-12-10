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
use SwagMigrationAssistant\Profile\Shopware\Converter\ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingCalculationType;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedShippingPriceLog;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class ShippingMethodConverterTest extends TestCase
{
    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var ShippingMethodConverter
     */
    private $shippingMethodConverter;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        $mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->shippingMethodConverter = new Shopware55ShippingMethodConverter($mappingService, $this->loggingService);

        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::randomHex());
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->context = Context::createDefaultContext();
        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new ShippingMethodDataSet(),
            0,
            250
        );

        $mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );

        $mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::DELIVERY_TIME,
            'default_delivery_time',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );

        $mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::CURRENCY,
            'EUR',
            $this->context,
            null,
            null,
            Uuid::randomHex()
        );
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->shippingMethodConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['translations']);
    }

    public function testConvertWithInvalidCalculation(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';
        $shippingMethodData[0]['calculation'] = '5';

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $logs = $this->loggingService->getLoggingArray();
        $error = new UnsupportedShippingCalculationType('', DefaultEntities::SHIPPING_METHOD, '15', '5');

        static::assertNotNull($convertResult->getUnmapped());
        static::assertNull($convertResult->getConverted());
        static::assertCount(1, $logs);
        static::assertSame($error->getCode(), $logs[0]['code']);
        static::assertSame($error->getSourceId(), $logs[0]['sourceId']);
        static::assertSame($error->getEntity(), $logs[0]['entity']);
        static::assertSame($error->getParameters()['type'], $logs[0]['parameters']['type']);
    }

    public function testConvertWithFactor(): void
    {
        $shippingMethodData = require __DIR__ . '/../../../_fixtures/shipping_method_data.php';
        $shippingMethodData[0]['shippingCosts'][0]['factor'] = 100.0;

        $convertResult = $this->shippingMethodConverter->convert($shippingMethodData[0], $this->context, $this->migrationContext);
        $logs = $this->loggingService->getLoggingArray();
        $error = new UnsupportedShippingPriceLog('', DefaultEntities::SHIPPING_METHOD_PRICE, '309', '15');

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getConverted());
        static::assertCount(1, $logs);
        static::assertSame($error->getCode(), $logs[0]['code']);
        static::assertSame($error->getSourceId(), $logs[0]['sourceId']);
        static::assertSame($error->getEntity(), $logs[0]['entity']);
        static::assertSame($error->getParameters()['shippingMethodId'], $logs[0]['parameters']['shippingMethodId']);
    }
}
