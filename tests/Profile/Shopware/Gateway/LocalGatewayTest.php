<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Language\LanguageCollection;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

#[Package('fundamentals@after-sales')]
class LocalGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    #[DataProvider('profileProvider')]
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
            $connection,
            $profile,
            null,
            new ProductDataSet()
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = static::getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');

        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gatewaySource = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );
        $migrationContext->setGateway($gatewaySource);
        $gatewayRegistry = new GatewayRegistry([
            $gatewaySource,
        ]);
        $gateway = $gatewayRegistry->getGateway($migrationContext);

        $this->expectException(ConnectionException::class);
        $gateway->read($migrationContext);
    }

    #[DataProvider('profileProvider')]
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
            $connection,
            $profile,
            null,
            new FooDataSet(),
        );

        $connectionFactory = new ConnectionFactory();
        $readerRegistry = static::getContainer()->get(ReaderRegistry::class);
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');

        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gatewaySource = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );
        $migrationContext->setGateway($gatewaySource);
        $gatewayRegistry = new GatewayRegistry([
            $gatewaySource,
        ]);

        $gateway = $gatewayRegistry->getGateway($migrationContext);

        try {
            $gateway->read($migrationContext);
        } catch (MigrationException $e) {
            static::assertSame(MigrationException::READER_NOT_FOUND, $e->getErrorCode());

            return;
        }

        static::fail('Expected exception not thrown');
    }

    #[DataProvider('profileProvider')]
    public function testReadEnvironmentInformationHasEmptyResult(string $profileName, ProfileInterface $profile): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName($profileName);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $connection,
            $profile,
        );

        $readerRegistry = static::getContainer()->get(ReaderRegistry::class);
        $connectionFactory = new ConnectionFactory();
        $localEnvironmentReader = new EnvironmentReader($connectionFactory);
        $localTableReader = new TableReader($connectionFactory);
        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');

        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gateway = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );
        $migrationContext->setGateway($gateway);
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertSame($response->getTotals(), []);
    }

    public function testGenerateFingerprintWithConfig(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([
            'dbName' => 'test',
            'dbUser' => 'test',
            'dbPassword' => 'test',
            'dbHost' => 'localhost',
            'dbPort' => '3306',
        ]);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $readerRegistry = new ReaderRegistry([]);
        $connectionFactory = $this->createMock(ConnectionFactoryInterface::class);

        $mockResult = $this->createMock(Result::class);
        $mockDbConnection = $this->createMock(Connection::class);
        $mockDbConnection->method('executeQuery')->willReturn($mockResult);

        $connectionFactory->method('createDatabaseConnection')->willReturn($mockDbConnection);

        $localEnvironmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $localEnvironmentReader->method('read')->willReturn([
            'defaultShopLanguage' => 'de_DE',
            'host' => 'sw55.local',
            'additionalData' => [],
            'defaultCurrency' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'config' => [
                'esdKey' => 'test-esd-key',
                'installationDate' => '2023-01-01 00:00:00',
            ],
        ]);

        $localTableReader = new TableReader(new ConnectionFactory());

        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');
        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gateway = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );

        $migrationContext->setGateway($gateway);
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNotNull($response->getFingerprint());
        static::assertIsString($response->getFingerprint());
        static::assertSame('Europe/Berlin', $response->getTimezone());
    }

    public function testGenerateFingerprintWithoutConfig(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([
            'dbName' => 'test',
            'dbUser' => 'test',
            'dbPassword' => 'test',
            'dbHost' => 'localhost',
            'dbPort' => '3306',
        ]);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $readerRegistry = new ReaderRegistry([]);
        $connectionFactory = $this->createMock(ConnectionFactoryInterface::class);

        $mockResult = $this->createMock(Result::class);
        $mockDbConnection = $this->createMock(Connection::class);
        $mockDbConnection->method('executeQuery')->willReturn($mockResult);

        $connectionFactory->method('createDatabaseConnection')->willReturn($mockDbConnection);

        $localEnvironmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $localEnvironmentReader->method('read')->willReturn([
            'defaultShopLanguage' => 'de_DE',
            'host' => 'sw55.local',
            'additionalData' => [],
            'defaultCurrency' => 'EUR',
            'timezone' => null,
        ]);

        $localTableReader = new TableReader(new ConnectionFactory());

        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');
        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gateway = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );

        $migrationContext->setGateway($gateway);
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNull($response->getFingerprint());
    }

    public function testGenerateFingerprintWithoutEsdKey(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([
            'dbName' => 'test',
            'dbUser' => 'test',
            'dbPassword' => 'test',
            'dbHost' => 'localhost',
            'dbPort' => '3306',
        ]);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $readerRegistry = new ReaderRegistry([]);
        $connectionFactory = $this->createMock(ConnectionFactoryInterface::class);

        $mockResult = $this->createMock(Result::class);
        $mockDbConnection = $this->createMock(Connection::class);
        $mockDbConnection->method('executeQuery')->willReturn($mockResult);

        $connectionFactory->method('createDatabaseConnection')->willReturn($mockDbConnection);

        $localEnvironmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $localEnvironmentReader->method('read')->willReturn([
            'defaultShopLanguage' => 'de_DE',
            'host' => 'sw55.local',
            'additionalData' => [],
            'defaultCurrency' => 'EUR',
            'timezone' => null,
            'config' => [
                'installationDate' => '2023-01-01 00:00:00',
            ],
        ]);

        $localTableReader = new TableReader(new ConnectionFactory());

        /** @var EntityRepository<CurrencyCollection> $currencyRepository */
        $currencyRepository = static::getContainer()->get('currency.repository');
        /** @var EntityRepository<LanguageCollection> $languageRepository */
        $languageRepository = static::getContainer()->get('language.repository');

        $gateway = new ShopwareLocalGateway(
            $readerRegistry,
            $localEnvironmentReader,
            $localTableReader,
            $connectionFactory,
            $currencyRepository,
            $languageRepository
        );

        $migrationContext->setGateway($gateway);
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNull($response->getFingerprint());
    }

    public static function profileProvider(): array
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
