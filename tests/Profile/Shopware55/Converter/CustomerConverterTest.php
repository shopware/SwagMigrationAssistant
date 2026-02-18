<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertEntityUnknownLog;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
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

#[Package('fundamentals@after-sales')]
class CustomerConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private Shopware55CustomerConverter $customerConverter;

    private DummyLoggingService $loggingService;

    private MigrationContextInterface $migrationContext;

    private DummyMappingService $mappingService;

    private string $connectionId;

    protected function setUp(): void
    {
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = new DummyMappingService();

        $validator = static::getContainer()->get('validator');

        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');

        $this->customerConverter = new Shopware55CustomerConverter(
            $this->mappingService,
            $this->loggingService,
            $validator,
            $salesChannelRepo,
            static::getContainer()->get(CountryLookup::class),
            static::getContainer()->get(LanguageLookup::class),
            static::getContainer()->get(CountryStateLookup::class),
        );

        $this->connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($this->connectionId);
        $connection->setName('shopware');
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            new CustomerDataSet(),
            $runId,
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

        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '3', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '4', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '5', $context, Uuid::randomHex(), [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'mr', $context, Uuid::randomHex(), [], Uuid::randomHex());
        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'ms', $context, Uuid::randomHex(), [], Uuid::randomHex());

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::COUNTRY_STATE, '3', $context, null, [], '019243e2514672debd864b2b979544f4');
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
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    #[DataProvider('requiredProperties')]
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
        static::assertNotNull($convertResult->getConverted());
    }

    /**
     * @return array<string, array<int, string|null>>
     */
    public static function requiredProperties(): array
    {
        return [
            'email is null' => ['email', null],
            'email is empty' => ['email', ''],
            'firstname is null' => ['firstname', null],
            'firstname is empty' => ['firstname', ''],
            'lastname is null' => ['lastname', null],
            'lastname is empty' => ['lastname', ''],
            'defaultpayment is null' => ['defaultpayment', null],
            'customerGroupId is empty' => ['customerGroupId', ''],
            'customerGroupId is null' => ['customerGroupId', null],
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
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
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
        static::assertNotNull($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
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
        $mapping = $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), PaymentMethodReader::SOURCE_ID, $context, null, [], Uuid::randomHex());
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
        static::assertSame(TestDefaults::SALES_CHANNEL, $converted['salesChannelId']);
        static::assertSame('Mustermann', $converted['lastName']);
        static::assertSame($mapping['entityId'], $converted['defaultPaymentMethodId']);
        static::assertCount(0, $this->loggingService->getLoggingArray());
    }

    public function testGetCustomerWithShopScope(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['shop'] = [
            'customer_scope' => '1',
        ];

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertIsArray($converted);
        static::assertNotNull($converted['boundSalesChannelId']);
    }

    public function testConvertCountryStateWithMapping(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', 'DE-NW'));
        $expectedStateId = static::getContainer()->get('country_state.repository')->searchIds($criteria, $context)->firstId();

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayHasKey('countryState', $converted['addresses'][0]);
        static::assertArrayHasKey('id', $converted['addresses'][0]['countryState']);
        static::assertSame($expectedStateId, $converted['addresses'][0]['countryState']['id']);
        static::assertSame('DE-NW', $converted['addresses'][0]['countryState']['shortCode']);
    }

    public function testConvertExistingCountryStateWithoutMapping(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][0]['state_id'] = '9999';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', 'DE-NW'));
        $expectedStateId = static::getContainer()->get('country_state.repository')->searchIds($criteria, $context)->firstId();

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayHasKey('countryState', $converted['addresses'][0]);
        static::assertArrayHasKey('id', $converted['addresses'][0]['countryState']);
        static::assertSame($expectedStateId, $converted['addresses'][0]['countryState']['id']);
    }

    public function testConvertNotExistingCountryStateWithoutMapping(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[0];
        $customerData['addresses'][0]['state_id'] = '9999';
        unset($customerData['addresses'][0]['state']);

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();

        static::assertNotNull($converted);
        static::assertArrayHasKey('id', $converted);
        static::assertArrayHasKey('addresses', $converted);
        static::assertArrayNotHasKey('countryStateId', $converted['addresses'][0]);

        $logs = $this->loggingService->getLoggingArray();

        static::assertCount(1, $logs);

        static::assertSame($logs[0]['code'], ConvertEntityUnknownLog::getCode());
    }

    public function testConvertBusinessCustomer(): void
    {
        $customerData = require __DIR__ . '/../../../_fixtures/customer_data.php';
        $customerData = $customerData[2];
        $customerData['addresses'][0]['company'] = 'Shopware AG';

        $context = Context::createDefaultContext();
        $convertResult = $this->customerConverter->convert(
            $customerData,
            $context,
            $this->migrationContext
        );

        $converted = $convertResult->getConverted();
        static::assertNotNull($converted);
        static::assertSame('Shopware AG', $converted['company']);
        static::assertSame(CustomerEntity::ACCOUNT_TYPE_BUSINESS, $converted['accountType']);
    }
}
