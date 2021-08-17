<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

class SalesChannelConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var Shopware55SalesChannelConverter
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
     * @var Connection
     */
    private $dbalConnection;

    protected function setUp(): void
    {
        $paymentMethodRepo = $this->getContainer()->get('payment_method.repository');
        $shippingMethodRepo = $this->getContainer()->get('shipping_method.repository');
        $countryRepo = $this->getContainer()->get('country.repository');
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $this->dbalConnection = $this->getContainer()->get('Doctrine\DBAL\Connection');

        $this->mappingService = new DummyMappingService();
        $this->loggingService = new DummyLoggingService();
        $this->converter = new Shopware55SalesChannelConverter(
            $this->mappingService,
            $this->loggingService,
            $paymentMethodRepo,
            $shippingMethodRepo,
            $countryRepo,
            $salesChannelRepo,
            null
        );

        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new SaleschannelDataSet(),
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
        $salesChannelData = require __DIR__ . '/../../../_fixtures/sales_channel_data.php';

        $context = Context::createDefaultContext();
        /** @var SwagMigrationConnectionEntity $connection */
        $connection = $this->migrationContext->getConnection();
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CUSTOMER_GROUP, '1', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '3', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '39', $context);

        $convertResult = $this->converter->convert($salesChannelData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertCount(3, $converted['languages']);
        static::assertSame('Deutsch', $converted['name']);

        $convertResult = $this->converter->convert($salesChannelData[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertCount(3, $converted['languages']);
        static::assertSame('Gartensubshop', $converted['name']);
    }

    public function testConvertWithInactiveRequirements(): void
    {
        $this->dbalConnection->executeStatement(
            'UPDATE payment_method SET active = 0;'
        );
        $this->dbalConnection->executeStatement(
            'UPDATE shipping_method SET active = 0;'
        );

        $salesChannelData = require __DIR__ . '/../../../_fixtures/sales_channel_data.php';

        $context = Context::createDefaultContext();
        /** @var SwagMigrationConnectionEntity $connection */
        $connection = $this->migrationContext->getConnection();
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CUSTOMER_GROUP, '1', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '3', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '39', $context);

        $defaultPaymentId = Uuid::randomHex();
        $defaultShippingId = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::PAYMENT_METHOD, 'default_payment_method', $context, null, null, $defaultPaymentId);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::SHIPPING_METHOD, 'default_shipping_method', $context, null, null, $defaultShippingId);

        $convertResult = $this->converter->convert($salesChannelData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        /** @var array $converted */
        $converted = $convertResult->getConverted();

        static::assertArrayHasKey('paymentMethodId', $converted);
        static::assertSame($defaultPaymentId, $converted['paymentMethodId']);
        static::assertArrayHasKey('paymentMethods', $converted);
        static::assertCount(1, $converted['paymentMethods']);
        static::assertSame($defaultPaymentId, $converted['paymentMethods'][0]['id']);

        static::assertArrayHasKey('shippingMethodId', $converted);
        static::assertSame($defaultShippingId, $converted['shippingMethodId']);
        static::assertArrayHasKey('shippingMethods', $converted);
        static::assertCount(1, $converted['shippingMethods']);
        static::assertSame($defaultShippingId, $converted['shippingMethods'][0]['id']);
    }
}
