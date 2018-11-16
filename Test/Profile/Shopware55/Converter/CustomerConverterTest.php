<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class CustomerConverterTest extends TestCase
{
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
        $this->customerConverter = new CustomerConverter($mappingService, $converterHelperService, $this->loggingService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->customerConverter->supports();

        static::assertSame(CustomerDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[0],
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    /**
     * @dataProvider requiredProperties
     */
    public function testConvertWithoutRequiredProperties(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData[$property] = $value;

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = sprintf('Customer-Entity could not converted cause of empty necessary field(s): %s.', $property);
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function requiredProperties(): array
    {
        return [
            ['email', null],
            ['email', ''],
            ['firstname', null],
            ['firstname', ''],
            ['lastname', null],
            ['lastname', ''],
        ];
    }

    public function testConvertGuestAccount(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[2],
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Test', $converted['lastName']);
        static::assertTrue($converted['guest']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCustomerWithoutNumber(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['customernumber'] = null;

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertSame('number-test@example.com', $converted['customerNumber']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCustomerWithoutPayment(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        unset($customerData['defaultpayment']);

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertSame(Defaults::PAYMENT_METHOD_SEPA, $converted['defaultPaymentMethodId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCustomerWithoutAddresses(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        unset($customerData['addresses']);

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        $description = 'Customer-Entity could not converted cause of empty address data.';
        static::assertSame($description, $logs[0]['logEntry']['description']);
    }

    public function testConvertCustomerWithoutValidAddresses(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[1];

        $customerData['addresses'][0]['firstname'] = '';
        $customerData['addresses'][1]['lastname'] = '';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(3, $logs);

        $description = 'Address-Entity could not converted cause of empty necessary field(s): firstname.';
        static::assertSame($description, $logs[0]['logEntry']['description']);

        $description = 'Address-Entity could not converted cause of empty necessary field(s): lastname.';
        static::assertSame($description, $logs[1]['logEntry']['description']);

        $description = 'Customer-Entity could not converted cause of empty address data.';
        static::assertSame($description, $logs[2]['logEntry']['description']);
    }

    public function testConvertWithCustomerGroupDiscounts(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[1],
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Kundengruppe-Netto', $converted['lastName']);
        static::assertSame(5.0, $converted['group']['discounts'][0]['percentageDiscount']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function requiredAddressProperties(): array
    {
        return [
            ['firstname', null],
            ['firstname', ''],
            ['lastname', null],
            ['lastname', ''],
            ['zipcode', null],
            ['zipcode', ''],
            ['city', null],
            ['city', ''],
            ['street', null],
            ['street', ''],
        ];
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutRequiredAddressPropertiesForBillingDefault(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][0][$property] = $value;

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Mustermannstraße 92', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();

        $description = sprintf('Address-Entity could not converted cause of empty necessary field(s): %s.', $property);
        static::assertSame($description, $logs[0]['logEntry']['description']);

        $description = 'Default billing address of customer is empty and will set with the default shipping address.';
        static::assertSame($description, $logs[1]['logEntry']['description']);

        static::assertCount(2, $logs);
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutRequiredAddressPropertiesForShippingDefault(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][1][$property] = $value;

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Musterstr. 55', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();

        $description = sprintf('Address-Entity could not converted cause of empty necessary field(s): %s.', $property);
        static::assertSame($description, $logs[0]['logEntry']['description']);

        $description = 'Default shipping address of customer is empty and will set with the default billing address.';
        static::assertSame($description, $logs[1]['logEntry']['description']);

        static::assertCount(2, $logs);
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutRequiredAddressPropertiesForDefaultBillingAndShipping(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][0][$property] = $value;
        $customerData['addresses'][1][$property] = $value;

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Uuid::uuid4()->getHex(),
            Uuid::uuid4()->getHex(),
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('group', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Musterstraße 3', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();

        $description = sprintf('Address-Entity could not converted cause of empty necessary field(s): %s.', $property);
        static::assertSame($description, $logs[0]['logEntry']['description']);
        static::assertSame($description, $logs[1]['logEntry']['description']);

        $description = 'Default billing and shipping address of customer is empty and will set with the first address.';
        static::assertSame($description, $logs[2]['logEntry']['description']);

        static::assertCount(3, $logs);
    }
}
