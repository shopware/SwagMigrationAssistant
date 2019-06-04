<?php declare(strict_types=1);

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
use SwagMigrationAssistant\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationAssistant\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Exception\AssociationEntityRequiredMissingException;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\TransactionStateReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use Symfony\Component\HttpFoundation\Response;

class OrderConverterTest extends TestCase
{
    use MigrationServicesTrait;

    /**
     * @var OrderConverter
     */
    private $orderConverter;

    /**
     * @var CustomerConverter
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

        $taxCalculator = new TaxCalculator(
            $rounding,
            $taxRuleCalculator
        );
        $this->orderConverter = new OrderConverter($mappingService, $taxCalculator, $this->loggingService);
        $this->customerConverter = new CustomerConverter($mappingService, $this->loggingService);

        $connectionId = Uuid::randomHex();
        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);
        $this->connection->setId($connectionId);
        $this->connection->setProfile($profile);

        $this->migrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new OrderDataSet(),
            0,
            250
        );

        $this->customerMigrationContext = new MigrationContext(
            $this->connection,
            $this->runId,
            new CustomerDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $mappingService->createNewUuid(
            $this->connection->getId(),
            DefaultEntities::SALES_CHANNEL,
            '1',
            $context,
            null,
            Defaults::SALES_CHANNEL
        );

        $mappingService->createNewUuid($connectionId, OrderStateReader::getMappingName(), '0', $context, [], Uuid::randomHex());
        $mappingService->createNewUuid($connectionId, TransactionStateReader::getMappingName(), '17', $context, [], Uuid::randomHex());

        $mappingService->createNewUuid($connectionId, PaymentMethodReader::getMappingName(), '3', $context, [], Uuid::randomHex());
        $mappingService->createNewUuid($connectionId, PaymentMethodReader::getMappingName(), '4', $context, [], Uuid::randomHex());
        $mappingService->createNewUuid($connectionId, PaymentMethodReader::getMappingName(), '5', $context, [], Uuid::randomHex());

        $mappingService->createNewUuid($connectionId, SalutationReader::getMappingName(), 'mr', $context, [], Uuid::randomHex());
        $mappingService->createNewUuid($connectionId, SalutationReader::getMappingName(), 'ms', $context, [], Uuid::randomHex());

        $mappingService->createNewUuid($connectionId, DeliveryTimeReader::getMappingName(), 'default_delivery_time', $context, [], Uuid::randomHex());

        $mappingService->createNewUuid($connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $context, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $mappingService->createNewUuid($connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $context, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->orderConverter->supports(Shopware55Profile::PROFILE_NAME, new OrderDataSet());

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
        $description = sprintf('Order-Entity could not be converted cause of empty necessary field(s): %s.', $missingProperty);
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertCount(1, $logs);
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
        unset($orderData['shippingMethod']);
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

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
            static::assertCount(1, $log['logEntry']['details']['fields']);
            static::assertTrue(
                $log['logEntry']['details']['fields'][0] === 'billingaddress'
                || $missingAddressProperty === $log['logEntry']['details']['fields'][0]
            );
        }
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
            static::assertSame(Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
        }
    }

    public function testConvertWithoutValidLineItems(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        foreach ($orderData['details'] as &$detail) {
            $detail['modus'] = 1;
            $detail['articleordernumber'] = '';
        }
        unset($detail);
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
        static::assertCount(3, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(Shopware55LogTypes::EMPTY_LINE_ITEM_IDENTIFIER, $log['logEntry']['code']);
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
            static::assertSame(Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
            static::assertCount(1, $log['logEntry']['details']['fields']);
            static::assertSame($log['logEntry']['details']['fields']['0'], 'paymentMethod');
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
            static::assertSame(Shopware55LogTypes::UNKNOWN_ORDER_STATE, $log['logEntry']['code']);
        }
    }
}
