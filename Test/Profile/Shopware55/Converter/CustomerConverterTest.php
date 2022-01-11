<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

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
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var DummyMappingService
     */
    private $mappingService;

    /**
     * @var string
     */
    private $connectionId;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new DummyMappingService();
        /** @var ValidatorInterface $validator */
        $validator = $this->getContainer()->get('validator');
        $this->customerConverter = new Shopware55CustomerConverter($this->mappingService, $this->loggingService, $validator);

        $this->connectionId = Uuid::randomHex();
        $this->runId = Uuid::randomHex();
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId($this->connectionId);
        $this->connection->setName('shopware');
        $this->connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $this->connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runId,
            new CustomerDataSet(),
            0,
            250
        );

        $context = Context::createDefaultContext();
        $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::SALES_CHANNEL,
            '1',
            $context,
            null,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '3', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '4', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '5', $context, Uuid::randomHex(), [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'mr', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'ms', $context, Uuid::randomHex(), [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->customerConverter->supports($this->migrationContext);

        static::assertTrue($supportsDefinition);
    }

    public function testConvert(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertWithInvalidEmail(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData[0]['email'] = '42';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[0],
            $context,
            $this->migrationContext
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION__INVALID_EMAIL_ADDRESS');
        static::assertSame($logs[0]['parameters']['email'], '42');
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
            $this->migrationContext
        );
        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER');
        static::assertSame($logs[0]['parameters']['emptyField'], $property);
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
            ['defaultpayment', null],
            ['customerGroupId', ''],
            ['customerGroupId', null],
        ];
    }

    public function testConvertGuestAccount(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData[2],
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
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
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertSame('number-1', $converted['customerNumber']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testConvertCustomerWithoutPaymentAndWithDefaultPayment(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        unset($customerData['defaultpayment']);

        $context = Context::createDefaultContext();
        $mapping = $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), 'default_payment_method', $context, null, [], Uuid::randomHex());
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();
        $logs = $this->loggingService->getLoggingArray();

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($converted);
        static::assertCount(0, $logs);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(Defaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertSame($mapping['entityUuid'], $converted['defaultPaymentMethodId']);
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
            $this->migrationContext
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER');
        static::assertSame($logs[0]['parameters']['sourceId'], $customerData['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], 'address data');
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
            $this->migrationContext
        );

        static::assertNull($convertResult->getConverted());

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(3, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[0]['parameters']['sourceId'], $customerData['addresses'][0]['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], 'firstname');

        static::assertSame($logs[1]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[1]['parameters']['sourceId'], $customerData['addresses'][1]['id']);
        static::assertSame($logs[1]['parameters']['emptyField'], 'lastname');

        static::assertSame($logs[2]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER');
        static::assertSame($logs[2]['parameters']['sourceId'], $customerData['id']);
        static::assertSame($logs[2]['parameters']['emptyField'], 'address data');
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
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Mustermannstraße 92', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(2, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[0]['parameters']['sourceId'], $customerData['addresses'][0]['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], $property);

        static::assertSame($logs[1]['code'], 'SWAG_MIGRATION_CUSTOMER_ENTITY_FIELD_REASSIGNED');
        static::assertSame($logs[1]['parameters']['emptyField'], 'default billing address');
        static::assertSame($logs[1]['parameters']['replacementField'], 'default shipping address');
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
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Musterstr. 55', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(2, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[0]['parameters']['sourceId'], $customerData['addresses'][1]['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], $property);

        static::assertSame($logs[1]['code'], 'SWAG_MIGRATION_CUSTOMER_ENTITY_FIELD_REASSIGNED');
        static::assertSame($logs[1]['parameters']['emptyField'], 'default shipping address');
        static::assertSame($logs[1]['parameters']['replacementField'], 'default billing address');
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
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);

        static::assertSame('Musterstraße 3', $converted['addresses'][0]['street']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultBillingAddressId']);
        static::assertSame($converted['addresses'][0]['id'], $converted['defaultShippingAddressId']);

        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(3, $logs);

        static::assertSame($logs[0]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[0]['parameters']['sourceId'], $customerData['addresses'][0]['id']);
        static::assertSame($logs[0]['parameters']['emptyField'], $property);

        static::assertSame($logs[1]['code'], 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS');
        static::assertSame($logs[1]['parameters']['sourceId'], $customerData['addresses'][1]['id']);
        static::assertSame($logs[1]['parameters']['emptyField'], $property);

        static::assertSame($logs[2]['code'], 'SWAG_MIGRATION_CUSTOMER_ENTITY_FIELD_REASSIGNED');
        static::assertSame($logs[2]['parameters']['emptyField'], 'default billing and shipping address');
        static::assertSame($logs[2]['parameters']['replacementField'], 'first address');
    }
}
