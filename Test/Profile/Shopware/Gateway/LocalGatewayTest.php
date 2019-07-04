<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use Doctrine\DBAL\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\LocalReaderNotFoundException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LocalEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LocalTableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LocalTableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ReaderRegistry;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

class LocalGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    public function testReadFailedNoCredentials(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields(
            [
                'dbName' => '',
                'dbUser' => '',
                'dbPassword' => '',
                'dbHost' => '',
                'dbPort' => '',
            ]
        );

        $migrationContext = new MigrationContext(
            $connection,
            '',
            new ProductDataSet()
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new LocalEnvironmentReader($connectionFactory);
        $localTableReader = new LocalTableReader($connectionFactory);
        $localTableCountReader = new LocalTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class));
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        $gatewaySource = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $localTableCountReader,
            $connectionFactory,
            $currencyRepository
        );
        $gatewayRegistry = new GatewayRegistry([
            $gatewaySource,
        ]);
        $gateway = $gatewayRegistry->getGateway($migrationContext);

        $this->expectException(ConnectionException::class);
        $gateway->read($migrationContext);
    }

    public function testReadWithUnknownEntityThrowsException(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields(
            [
                'dbName' => '',
                'dbUser' => '',
                'dbPassword' => '',
                'dbHost' => '',
                'dbPort' => '',
            ]
        );

        $migrationContext = new MigrationContext(
            $connection,
            '',
            new FooDataSet()
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new LocalEnvironmentReader($connectionFactory);
        $localTableReader = new LocalTableReader($connectionFactory);
        $localTableCountReader = new LocalTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class));
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        $gatewaySource = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $localTableCountReader,
            $connectionFactory,
            $currencyRepository
        );
        $gatewayRegistry = new GatewayRegistry([
            $gatewaySource,
        ]);

        $gateway = $gatewayRegistry->getGateway($migrationContext);

        $this->expectException(LocalReaderNotFoundException::class);
        $gateway->read($migrationContext);
    }

    public function testReadEnvironmentInformationHasEmptyResult(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $connection
        );

        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $connectionFactory = new ConnectionFactory();
        $localEnvironmentReader = new LocalEnvironmentReader($connectionFactory);
        $localTableReader = new LocalTableReader($connectionFactory);
        $localTableCountReader = new LocalTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class));
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        $gateway = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $localTableCountReader,
            $connectionFactory,
            $currencyRepository
        );
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertSame($response->getTotals(), []);
    }
}
