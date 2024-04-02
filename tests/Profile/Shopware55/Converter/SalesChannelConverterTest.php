<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Converter;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Mapping\DummyMappingService;

#[Package('services-settings')]
class SalesChannelConverterTest extends TestCase
{
    use IntegrationTestBehaviour;

    private MigrationContext $migrationContext;

    private Shopware55SalesChannelConverter $converter;

    private DummyMappingService $mappingService;

    private Connection $dbalConnection;

    protected function setUp(): void
    {
        $paymentMethodRepo = static::getContainer()->get('payment_method.repository');
        $shippingMethodRepo = static::getContainer()->get('shipping_method.repository');
        $countryRepo = static::getContainer()->get('country.repository');
        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');
        $this->dbalConnection = static::getContainer()->get(Connection::class);

        $this->mappingService = new DummyMappingService();
        $loggingService = new DummyLoggingService();
        $this->converter = new Shopware55SalesChannelConverter(
            $this->mappingService,
            $loggingService,
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
            new SalesChannelDataSet(),
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
        $connection = $this->migrationContext->getConnection();
        static::assertNotNull($connection);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CUSTOMER_GROUP, '1', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '3', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '39', $context);

        $convertResult = $this->converter->convert($salesChannelData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();
        static::assertIsArray($converted);

        static::assertNull($convertResult->getUnmapped());
        static::assertNotNull($convertResult->getMappingUuid());
        static::assertArrayHasKey('id', $converted);
        static::assertCount(3, $converted['languages']);
        static::assertSame('Deutsch', $converted['name']);

        $convertResult = $this->converter->convert($salesChannelData[1], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();
        static::assertIsArray($converted);

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
        $connection = $this->migrationContext->getConnection();
        static::assertNotNull($connection);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CUSTOMER_GROUP, '1', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '3', $context);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::CATEGORY, '39', $context);

        $defaultPaymentId = Uuid::randomHex();
        $defaultShippingId = Uuid::randomHex();
        $this->mappingService->getOrCreateMapping($connection->getId(), PaymentMethodReader::getMappingName(), PaymentMethodReader::SOURCE_ID, $context, null, null, $defaultPaymentId);
        $this->mappingService->getOrCreateMapping($connection->getId(), DefaultEntities::SHIPPING_METHOD, 'default_shipping_method', $context, null, null, $defaultShippingId);

        $convertResult = $this->converter->convert($salesChannelData[0], $context, $this->migrationContext);
        $this->converter->writeMapping($context);
        $converted = $convertResult->getConverted();
        static::assertIsArray($converted);

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
