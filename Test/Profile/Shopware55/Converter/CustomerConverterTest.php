<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Profile\Shopware55\Converter\CustomerConverter;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class CustomerConverterTest extends TestCase
{
    /**
     * @var CustomerConverter
     */
    private $customerConverter;

    protected function setUp()
    {
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->customerConverter = new CustomerConverter($mappingService, $converterHelperService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->customerConverter->supports();

        static::assertSame(CustomerDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData[0],
            $context,
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
    }

    /**
     * @dataProvider requiredProperties
     */
    public function testConvertWithoutRequiredProperties(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData[$property] = $value;

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());
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

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData[2],
            $context,
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
    }

    public function testConvertCustomerWithoutNumber(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['customernumber'] = null;

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
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
        static::assertSame('number-max_mustermann@muster.de', $converted['customerNumber']);
    }

    public function testConvertCustomerWithoutPayment(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        unset($customerData['defaultpayment']);

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
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
    }

    public function testConvertCustomerWithoutAddresses(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        unset($customerData['addresses']);

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());
    }

    public function testConvertCustomerWithoutValidAddresses(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[1];

        $customerData['addresses'][0]['firstname'] = '';
        $customerData['addresses'][1]['firstname'] = '';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            Defaults::CATALOG,
            Defaults::SALES_CHANNEL
        );

        static::assertNull($convertResult->getConverted());
    }

    public function testConvertWithCustomerGroupDiscounts(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData[1],
            $context,
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

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
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
    }

    /**
     * @dataProvider requiredAddressProperties
     */
    public function testConvertWithoutRequiredAddressPropertiesForShippingDefault(string $property, ?string $value): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][1][$property] = $value;

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
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

        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
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
    }
}
