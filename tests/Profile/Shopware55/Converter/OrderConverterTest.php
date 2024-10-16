<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\Test\TestDefaults;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CurrencyLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
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
use Symfony\Component\Validator\Validation;

#[Package('services-settings')]
class OrderConverterTest extends TestCase
{
    use KernelTestBehaviour;
    use MigrationServicesTrait;

    private Shopware55OrderConverter $orderConverter;

    private Shopware55CustomerConverter $customerConverter;

    private DummyLoggingService $loggingService;

    private MigrationContext $migrationContext;

    private MigrationContext $customerMigrationContext;

    private DummyMappingService $mappingService;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new DummyMappingService();
        $taxCalculator = new TaxCalculator();

        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');

        $currencyLookup = $this->createMock(CurrencyLookup::class);
        $currencyLookup->method('get')->willReturn('b7d2554b0ce847cd82f3ac9bd1c0dfca');
        $this->orderConverter = new Shopware55OrderConverter(
            $this->mappingService,
            $this->loggingService,
            $taxCalculator,
            $salesChannelRepo,
            $this->getContainer()->get(CountryLookup::class),
            $currencyLookup,
            $this->getContainer()->get(LanguageLookup::class),
            $this->getContainer()->get(CountryStateLookup::class)
        );

        $this->customerConverter = new Shopware55CustomerConverter(
            $this->mappingService,
            $this->loggingService,
            Validation::createValidator(),
            $salesChannelRepo,
            $this->createMock(CountryLookup::class),
            $this->createMock(LanguageLookup::class),
            $this->createMock(CountryStateLookup::class),
        );

        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($connectionId);
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setName('shopware');

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new OrderDataSet(),
            0,
            250
        );

        $this->customerMigrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection,
            $runId,
            new CustomerDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $this->mappingService->getOrCreateMapping(
            $connection->getId(),
            DefaultEntities::SALES_CHANNEL,
            '1',
            $context,
            null,
            null,
            TestDefaults::SALES_CHANNEL
        );

        $this->mappingService->getOrCreateMapping($connectionId, OrderStateReader::getMappingName(), '0', $context, null, [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($connectionId, TransactionStateReader::getMappingName(), '17', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '3', $context, null, [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '4', $context, null, [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($connectionId, PaymentMethodReader::getMappingName(), '5', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, SalutationReader::getMappingName(), 'mr', $context, null, [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($connectionId, SalutationReader::getMappingName(), 'ms', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, DeliveryTimeReader::getMappingName(), DeliveryTimeReader::SOURCE_ID, $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $context, null, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->getOrCreateMapping($connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $context, null, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $this->mappingService->getOrCreateMapping($connectionId, DefaultEntities::SHIPPING_METHOD, '14', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, OrderDeliveryStateReader::getMappingName(), '0', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, DefaultEntities::MEDIA, 'esd_4', $context, null, [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($connectionId, DefaultEntities::COUNTRY_STATE, '3', $context, null, [], '019243e2514672debd864b2b979544f4');
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->orderConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
        static::assertArrayHasKey('lineItems', $converted);
        static::assertArrayHasKey('options', $converted['lineItems'][0]['payload']);
        static::assertSame([], $converted['lineItems'][0]['payload']['options']);
        static::assertNotNull($converted['itemRounding']);
        static::assertNotNull($converted['totalRounding']);

        $priceDefinition = $converted['lineItems'][0]['priceDefinition'];
        static::assertInstanceOf(QuantityPriceDefinition::class, $priceDefinition);
        static::assertTrue($priceDefinition->isCalculated());
        static::assertSame(459.95, $priceDefinition->getPrice());
        static::assertSame(2, $priceDefinition->getQuantity());

        $creditPriceDefinition = $converted['lineItems'][1]['priceDefinition'];
        static::assertInstanceOf(AbsolutePriceDefinition::class, $creditPriceDefinition);
        static::assertSame(-2.0, $creditPriceDefinition->getPrice());

        static::assertTrue(isset($converted['lineItems'][0]['downloads'][0]['mediaId']));
        static::assertFalse($converted['lineItems'][0]['downloads'][0]['accessGranted']);

        // Change payment status to grant the access
        $orderData[0]['cleared'] = '12';
        $convertResult = $this->orderConverter->convert(
            $orderData[0],
            $context,
            $this->migrationContext
        );
        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertTrue($converted['lineItems'][0]['downloads'][0]['accessGranted']);
    }

    public function testConvertShouldSetCalculatedTaxesInOrderDelivery(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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
        static::assertIsArray($converted);
        static::assertArrayHasKey('shippingCosts', $converted);
        $shippingCosts = $converted['shippingCosts'];
        static::assertInstanceOf(CalculatedPrice::class, $shippingCosts);
        $result = $shippingCosts->getCalculatedTaxes()->first();
        static::assertInstanceOf(CalculatedTax::class, $result);
        $taxRule = $shippingCosts->getTaxRules()->first();
        static::assertInstanceOf(TaxRule::class, $taxRule);

        static::assertSame(9.9, $result->getPrice());
        static::assertSame(19.0, $result->getTaxRate());
        static::assertSame(1.58, $result->getTax());

        static::assertSame(19.0, $taxRule->getTaxRate());
        static::assertSame(100.0, $taxRule->getPercentage());

        static::assertArrayHasKey('deliveries', $converted);
        $deliveryShippingCosts = $converted['deliveries'][0]['shippingCosts'];
        static::assertInstanceOf(CalculatedPrice::class, $deliveryShippingCosts);
        $deliveryResult = $deliveryShippingCosts->getCalculatedTaxes()->first();
        static::assertInstanceOf(CalculatedTax::class, $deliveryResult);
        $deliveryTaxRule = $shippingCosts->getTaxRules()->first();
        static::assertInstanceOf(TaxRule::class, $deliveryTaxRule);

        static::assertSame(9.9, $deliveryResult->getPrice());
        static::assertSame(19.0, $deliveryResult->getTaxRate());
        static::assertSame(1.58, $deliveryResult->getTax());

        static::assertSame(19.0, $deliveryTaxRule->getTaxRate());
        static::assertSame(100.0, $deliveryTaxRule->getPercentage());
    }

    public function testConvertWithoutCustomer(): void
    {
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';

        $context = Context::createDefaultContext();

        try {
            $this->orderConverter->convert($orderData[0], $context, $this->migrationContext);
        } catch (\Exception $e) {
            static::assertInstanceOf(MigrationException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame(MigrationException::ASSOCIATION_ENTITY_REQUIRED_MISSING, $e->getErrorCode());

            static::assertArrayHasKey('missingEntity', $e->getParameters());
            static::assertArrayHasKey('entity', $e->getParameters());
            static::assertSame('order', $e->getParameters()['entity']);
            static::assertSame('customer', $e->getParameters()['missingEntity']);
        }
    }

    public function testConvertNetOrder(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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
        static::assertNotNull($converted);

        $cartPrice = $converted['price'];
        static::assertInstanceOf(CartPrice::class, $cartPrice);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(['b2b-tracking-code'], $converted['deliveries'][0]['trackingCodes']);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('mustermann@b2b.de', $converted['orderCustomer']['email']);
        static::assertSame($cartPrice->getTaxStatus(), CartPrice::TAX_STATE_NET);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertTaxFreeOrder(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[1],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[2],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertCount(0, $this->loggingService->getLoggingArray());

        $cartPrice = $converted['price'];
        static::assertInstanceOf(CartPrice::class, $cartPrice);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('orderCustomer', $converted);
        static::assertArrayHasKey('deliveries', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('mustermann@b2b.de', $converted['orderCustomer']['email']);

        static::assertSame($cartPrice->getTaxStatus(), CartPrice::TAX_STATE_FREE);
        static::assertEquals($cartPrice->getTaxRules()->first(), new TaxRule(0.0));

        $lineItem0Price = $converted['lineItems'][0]['price'];
        static::assertInstanceOf(CalculatedPrice::class, $lineItem0Price);
        static::assertEquals($lineItem0Price->getCalculatedTaxes()->first(), new CalculatedTax(0.0, 0.0, 0.0));
    }

    #[DataProvider('requiredProperties')]
    public function testConvertWithoutRequiredProperties(string $missingProperty): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

    /**
     * @return list<list<string>>
     */
    public static function requiredProperties(): array
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
        [$customerData, $orderData] = $this->getFixtureData();
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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayNotHasKey('lineItems', $converted);
        static::assertCount(0, $converted['transactions']);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutShippingMethod(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertSame([], $converted['deliveries']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithoutShippingAddress(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertSame($converted['addresses'][0], $converted['deliveries'][0]['shippingOrderAddress']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithDifferentAdresses(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $orderData = $orderData[0];
        $context = Context::createDefaultContext();

        $orderData['shippingaddress']['firstname'] = 'John';
        $orderData['shippingaddress']['lastname'] = 'Doe';

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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertNotSame($converted['billingAddressId'], $converted['deliveries'][0]['shippingOrderAddress']['id']);
        static::assertSame('John', $converted['deliveries'][0]['shippingOrderAddress']['firstName']);
        static::assertSame('Doe', $converted['deliveries'][0]['shippingOrderAddress']['lastName']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    /**
     * @return list<list<string>>
     */
    public static function requiredAddressProperties(): array
    {
        return [
            ['firstname'],
            ['lastname'],
            ['zipcode'],
            ['city'],
            ['street'],
        ];
    }

    #[DataProvider('requiredAddressProperties')]
    public function testConvertWithoutValidBillingAddress(string $missingAddressProperty): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

    #[DataProvider('requiredAddressProperties')]
    public function testConvertWithoutValidShippingAddress(string $missingProperty): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('test@example.com', $converted['orderCustomer']['email']);
        static::assertCount(1, $this->loggingService->getLoggingArray());

        foreach ($this->loggingService->getLoggingArray() as $log) {
            static::assertSame('SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_ORDER_ADDRESS', $log['code']);
        }
    }

    public function testConvertWithoutPaymentName(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
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
        [$customerData, $orderData] = $this->getFixtureData();
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

    public function testConvertWithOrderLanguage(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $context = Context::createDefaultContext();

        $locale = new LocaleEntity();
        $locale->setCode('de-DE');
        $locale->setId(Defaults::LANGUAGE_SYSTEM);

        $language = new LanguageEntity();
        $language->setLocale($locale);
        $language->setLocaleId($locale->getId());

        $languageLookup = $this->createMock(LanguageLookup::class);
        $languageLookup->method('get')->willReturn(DummyMappingService::DEFAULT_LANGUAGE_UUID);
        $languageLookup->method('getLanguageEntity')->willReturn($language);

        $currencyLookup = $this->createMock(CurrencyLookup::class);
        $currencyLookup->method('get')->willReturn('b7d2554b0ce847cd82f3ac9bd1c0dfca');

        $orderConverter = $this->orderConverter = new Shopware55OrderConverter(
            $this->mappingService,
            $this->loggingService,
            new TaxCalculator(),
            static::getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get(CountryLookup::class),
            $currencyLookup,
            $languageLookup,
            $this->createMock(CountryStateLookup::class)
        );

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $orderData[0]['locale'] = 'af-ZA';
        $convertResult = $orderConverter->convert(
            $orderData[0],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertCount(0, $this->loggingService->getLoggingArray());
        static::assertSame(DummyMappingService::DEFAULT_LANGUAGE_UUID, $converted['languageId']);
    }

    public function testConvertWithDuplicatedEMails(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $context = Context::createDefaultContext();

        foreach ([0, 1] as $index) {
            $customerData[$index]['email'] = 'd2023c93-81d8-4d2e-8bac-d29edde45374@example.com';
            $customerData[$index]['accountmode'] = \sprintf('%d', $index);

            $orderData[$index]['userID'] = $customerData[$index]['id'];
            $orderData[$index]['customer']['id'] = $customerData[$index]['id'];
            $orderData[$index]['customer']['email'] = $customerData[$index]['email'];
            $orderData[$index]['customer']['accountmode'] = $customerData[$index]['accountmode'];
            $orderData[$index]['billingaddress']['userID'] = $customerData[$index]['id'];
            $orderData[$index]['shippingaddress']['userID'] = $customerData[$index]['id'];
        }

        $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->customerMigrationContext
        );

        $this->customerConverter->convert(
            $customerData[1],
            $context,
            $this->customerMigrationContext
        );

        $convertFirstResult = $this->orderConverter->convert(
            $orderData[0],
            $context,
            $this->migrationContext
        );

        $convertSecondResult = $this->orderConverter->convert(
            $orderData[1],
            $context,
            $this->migrationContext
        );

        $convertedFirst = $convertFirstResult->getConverted();
        $convertedSecond = $convertSecondResult->getConverted();

        static::assertNotNull($convertedFirst);
        static::assertNotNull($convertedSecond);

        static::assertNotSame($convertedFirst['orderCustomer']['customerId'], $convertedSecond['orderCustomer']['customerId']);
    }

    public function testConvertCountryStateWithMapping(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $orderData = $orderData[0];

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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', 'DE-NW'));
        $expectedStateId = $this->getContainer()->get('country_state.repository')->searchIds($criteria, $context)->firstId();

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);

        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayHasKey('countryState', $converted['addresses'][0]);
        static::assertArrayHasKey('id', $converted['addresses'][0]['countryState']);
        static::assertSame($expectedStateId, $converted['addresses'][0]['countryState']['id']);

        static::assertArrayHasKey('deliveries', $converted);
        static::assertArrayHasKey('shippingOrderAddress', $converted['deliveries'][0]);
        static::assertArrayHasKey('countryState', $converted['deliveries'][0]['shippingOrderAddress']);
        static::assertArrayHasKey('id', $converted['deliveries'][0]['shippingOrderAddress']['countryState']);
        static::assertSame($expectedStateId, $converted['deliveries'][0]['shippingOrderAddress']['countryState']['id']);
    }

    public function testConvertExistingCountryStateWithoutMapping(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $orderData = $orderData[0];
        $orderData['billingaddress']['stateID'] = '9999';

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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', 'DE-NW'));
        $expectedStateId = $this->getContainer()->get('country_state.repository')->searchIds($criteria, $context)->firstId();

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayHasKey('countryState', $converted['addresses'][0]);
        static::assertArrayHasKey('id', $converted['addresses'][0]['countryState']);
        static::assertSame($expectedStateId, $converted['deliveries'][0]['shippingOrderAddress']['countryState']['id']);
    }

    public function testConvertNotExistingCountryStateWithoutMapping(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $orderData = $orderData[0];
        $orderData['billingaddress']['stateID'] = '9999';
        unset($orderData['billingaddress']['state']);

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

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayNotHasKey('countryState', $converted['addresses'][0]);

        $logs = $this->loggingService->getLoggingArray();

        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_COUNTRY_STATE_ENTITY_UNKNOWN');
        static::assertSame($logs[0]['parameters']['sourceId'], '9999');
        static::assertSame($logs[0]['parameters']['entity'], DefaultEntities::COUNTRY_STATE);
        static::assertSame($logs[0]['parameters']['requiredForSourceId'], $orderData['id']);
        static::assertSame($logs[0]['parameters']['requiredForEntity'], DefaultEntities::ORDER);
    }

    public function testConvertWithShippingTaxRateNotSet(): void
    {
        [$customerData, $orderData] = $this->getFixtureData();
        $context = Context::createDefaultContext();

        $this->customerConverter->convert(
            $customerData[1],
            $context,
            $this->customerMigrationContext
        );

        $convertResult = $this->orderConverter->convert(
            $orderData[2],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        $deliveryShippingCosts = $converted['deliveries'][0]['shippingCosts'] ?? null;
        static::assertInstanceOf(CalculatedPrice::class, $deliveryShippingCosts);
        static::assertSame(200.0, $deliveryShippingCosts->getTotalPrice());
        $calculatedTax = $deliveryShippingCosts->getCalculatedTaxes()->first();
        static::assertInstanceOf(CalculatedTax::class, $calculatedTax);
        static::assertSame(20.0, $calculatedTax->getTaxRate());
        static::assertSame(40.0, $calculatedTax->getTax());
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function getFixtureData(): array
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $orderData = require __DIR__ . '/../../../_fixtures/order_data.php';

        return [$customerData, $orderData];
    }
}
