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
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\LocalReaderNotFoundException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ReaderRegistry;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

class LocalGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @dataProvider profileProvider
     */
    public function testReadFailedNoCredentials(string $profileName, ProfileInterface $profile): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName($profileName);
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
            $profile,
            $connection,
            '',
            new ProductDataSet()
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        $localTableCountReader = new TableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());
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

    /**
     * @dataProvider profileProvider
     */
    public function testReadWithUnknownEntityThrowsException(string $profileName, ProfileInterface $profile): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName($profileName);
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
            $profile,
            $connection,
            '',
            new FooDataSet()
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        $localTableCountReader = new TableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());
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

    /**
     * @dataProvider profileProvider
     */
    public function testReadEnvironmentInformationHasEmptyResult(string $profileName, ProfileInterface $profile): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName($profileName);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $profile,
            $connection
        );

        $readerRegistry = $this->getContainer()->get(ReaderRegistry::class);
        $connectionFactory = new ConnectionFactory();
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        $localTableCountReader = new TableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());
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

    public function profileProvider()
    {
        return [
            [
                Shopware54Profile::PROFILE_NAME,
                new Shopware54Profile(),
            ],
            [
                Shopware55Profile::PROFILE_NAME,
                new Shopware55Profile(),
            ],
            [
                Shopware56Profile::PROFILE_NAME,
                new Shopware56Profile(),
            ],
        ];
    }
}
