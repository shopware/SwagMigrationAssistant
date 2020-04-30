<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\PriceRounding;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxRuleCalculator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\AssociationEntityRequiredMissingException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderDeliveryStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TransactionStateReader;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use Symfony\Component\HttpFoundation\Response;

class OrderConverterTest extends TestCase
{
    use MigrationServicesTrait;

    /**
     * @var Shopware55OrderConverter
     */
    private $orderConverter;

    /**
     * @var Shopware55CustomerConverter
     */
    private $customerConverter;

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
     * @var MigrationContext
     */
    private $migrationContext;

    /**
     * @var MigrationContext
     */
    private $customerMigrationContext;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $mappingService = new DummyMappingService();
        $rounding = new PriceRounding();
        $taxRuleCalculator = new TaxRuleCalculator($rounding);

        $taxCalculator = new TaxCalculator($taxRuleCalculator);
        $this->orderConverter = new Shopware55OrderConverter($mappingService, $this->loggingService, $taxCalculator);
        $this->customerConverter = new Shopware55CustomerConverter($mappingService, $this->loggingService);

        $connectionId = Uuid::randomHex();
        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId($connectionId);
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $this->connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new OrderDataSet(),
            0,
            250
        );

        $this->customerMigrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CustomerDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::SALES_CHANNEL,
            '1',
            $context,
            null,
            null,
            Defaults::SALES_CHANNEL
        );

        $mappingService->getOrCreateMapping($connectionId, OrderStateReader::getMappingName(), '0', $context, null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($connectionId, TransactionStateReader::getMappingName(), '17', $context, null, [], Uuid::randomHex());

        $mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '3', $context, null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '4', $context, null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '5', $context, null, [], Uuid::randomHex());

        $mappingService->getOrCreateMapping($connectionId, SalutationReader::getMappingName(), 'mr', $context, null, [], Uuid::randomHex());
        $mappingService->getOrCreateMapping($connectionId, SalutationReader::getMappingName(), 'ms', $context, null, [], Uuid::randomHex());

        $mappingService->getOrCreateMapping($connectionId, DeliveryTimeReader::getMappingName(), 'default_delivery_time', $context, null, [], Uuid::randomHex());

        $mappingService->getOrCreateMapping($connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $context, null, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $mappingService->getOrCreateMapping($connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $context, null, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $mappingService->getOrCreateMapping($connectionId, DefaultEntities::SHIPPING_METHOD, '14', $context, null, [], Uuid::randomHex());

        $mappingService->getOrCreateMapping($connectionId, OrderDeliveryStateReader::getMappingName(), '0', $context, null, [], Uuid::randomHex());
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->orderConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[0],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutCustomer(): void
    {
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';

        $context = Context::createDefaultContext();
        try {
            $this->orderConverter->convert($orderData[0], $context, $this->migrationContext);
        } catch (\Exception $e) {
            /* @var AssociationEntityRequiredMissingException $e */
            static::assertInstanceOf(AssociationEntityRequiredMissingException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());

            static::assertArrayHasKey('missingEntity', $e->getParameters());
            static::assertArrayHasKey('entity', $e->getParameters());
            static::assertSame('order', $e->getParameters()['entity']);
            static::assertSame('customer', $e->getParameters()['missingEntity']);
        }
    }

    public function testConvertNetOrder(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[1],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[1],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();
        /** @var CartPrice $cartPrice */
        $cartPrice = $converted['price'];

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('mustermann@b2b.de', $converted['orderCustomer']['email']);
        static::assertSame($cartPrice->getTaxStatus(), CartPrice::TAX_STATE_NET);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    /**
     * @dataProvider requiredProperties
     */
    public function testConvertWithoutRequiredProperties(string $missingProperty): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData[$missingProperty]);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER');
        static::assertSame($logs[0]['parameters']['sourceId'], $orderData['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], $missingProperty);
    }

    public function requiredProperties(): array
    {
        return [
            ['billingaddress'],
            ['payment'],
            ['customer'],
            ['currencyFactor'],
            ['currency'],
            ['status'],
        ];
    }

    public function testConvertWithoutOrderDetails(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['details']);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayNotHasKey('lineItems', $converted);
        static::assertCount(0, $converted['transactions']);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutShippingMethod(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['dispatchID']);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertSame([], $converted['deliveries']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutShippingAddress(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['shippingaddress']);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertSame($converted['addresses'][0], $converted['deliveries'][0]['shippingOrderAddress']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function requiredAddressProperties(): array
    {
        return [
            ['firstname'],
            ['lastname'],
            ['zipcode'],
            ['city'],
            ['street'],
        ];
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutValidBillingAddress(string $missingAddressProperty): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['billingaddress'][$missingAddressProperty]);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(2, $this->loggingService->getLoggingArray());

        $validLog = 0;
        foreach ($this->loggingService->getLoggingArray() as $log) {
            if ($log['code'] === 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER_ADDRESS' || $log['code'] === 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER') {
                ++$validLog;
            }
        }

        static::assertSame(2, $validLog);
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutValidShippingAddress(string $missingProperty): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['shippingaddress'][$missingProperty]);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER_ADDRESS', $log['code']);
        }
    }

    public function testConvertWithoutPaymentName(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['payment']['name']);
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER', $log['code']);
            static::assertSame($log['parameters']['emptyField'], 'paymentMethod');
        }
    }

    public function testConvertWithoutKnownOrderState(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        $orderData['status'] = 100;
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame('SWAG_MIGRATION_ORDER_STATE_ENTITY_UNKNOWN', $log['code']);
        }
    }
}
