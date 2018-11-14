<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\PriceRounding;
use Shopware\Core\Checkout\Cart\Tax\PercentageTaxRuleCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxRuleCalculator;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\OrderConverter;
use SwagMigrationNext\Profile\Shopware55\Exception\AssociationEntityRequiredMissingException;
use SwagMigrationNext\Profile\Shopware55\Logging\LoggingType;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class OrderConverterTest extends TestCase
{
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

    protected function setUp()
    {
        $this->loggingService = new DummyLoggingService();
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $rounding = new PriceRounding(2);
        $taxRuleCalculator = new TaxRuleCalculator($rounding);

        $taxCalculator = new TaxCalculator(
            $rounding,
            [
                $taxRuleCalculator,
                new PercentageTaxRuleCalculator($taxRuleCalculator),
            ]
        );
        $this->orderConverter = new OrderConverter($mappingService, $converterHelperService, $taxCalculator, $this->loggingService);
        $this->customerConverter = new CustomerConverter($mappingService, $converterHelperService, $this->loggingService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->orderConverter->supports();

        static::assertSame(OrderDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $context = Context::createDefaultContext();

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
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
        $this->expectException(AssociationEntityRequiredMissingException::class);
        $this->expectExceptionMessage('Mapping of "customer" is missing, but it is a required association for "order". Import "customer" first');
        $this->orderConverter->convert($orderData[0], $context, Uuid::uuid4()->getHex(), Defaults::CATALOG, Defaults::SALES_CHANNEL);
    }

    public function testConvertNetOrder(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $context = Context::createDefaultContext();

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[1],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[1],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('mustermann@b2b.de', $converted['orderCustomer']['email']);
        static::assertTrue($converted['isNet']);
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        $description = sprintf('Order-Entity could not converted cause of empty necessary field(s): %s.', $missingProperty);
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
            ['paymentcurrency'],
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertSame($converted['billingAddress'], $converted['deliveries'][0]['shippingOrderAddress']);
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(2, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(LoggingType::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
            static::assertCount(1, $log['logEntry']['details']['fields']);
            static::assertTrue(
                $log['logEntry']['details']['fields'][0] === 'billingaddress' ||
                $missingAddressProperty === $log['logEntry']['details']['fields'][0]
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(LoggingType::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
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
        $context = Context::createDefaultContext();

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(3, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(LoggingType::EMPTY_LINE_ITEM_IDENTIFIER, $log['logEntry']['code']);
        }
    }

    public function testConvertWithoutPaymentName(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';
        $orderData = $orderData[0];
        unset($orderData['payment']['name']);
        $context = Context::createDefaultContext();

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(LoggingType::EMPTY_NECESSARY_DATA_FIELDS, $log['logEntry']['code']);
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

        $profileId = Uuid::uuid4()->getHex();
        $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $convertResult = $this->orderConverter->convert(
            $orderData,
            $context,
            Uuid::uuid4()->getHex(),
            $profileId,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($converted);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame(LoggingType::UNKNOWN_ORDER_STATE, $log['logEntry']['code']);
        }
    }
}
