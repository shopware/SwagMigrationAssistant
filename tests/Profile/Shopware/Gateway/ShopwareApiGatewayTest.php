<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader\EnvironmentDummyReader;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader\TableCountDummyReader;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

#[Package('fundamentals@after-sales')]
class ShopwareApiGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile(),
            null,
            new FooDataSet(),
            ''
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);
        $environmentReader = new EnvironmentReader($connectionFactory);
        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );
        $migrationContext->setGateway($gateway);

        try {
            $gateway->read($migrationContext);
        } catch (MigrationException $e) {
            static::assertSame(MigrationException::READER_NOT_FOUND, $e->getErrorCode());

            return;
        }

        static::fail('Expected exception not thrown');
    }

    public function testReadEnvironmentInformationFailed(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());
        $connection->setCredentialFields([
            'endpoint' => 'testing',
            'apiUser' => 'testing',
            'apiKey' => 'testing',
        ]);
        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);
        $environmentReader = new EnvironmentReader($connectionFactory);
        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertSame($response->getTotals(), []);
        static::assertNotNull($response->getRequestStatus());
        static::assertSame($response->getRequestStatus()->getCode(), MigrationException::API_CONNECTION_ERROR);
        static::assertFalse($response->getRequestStatus()->getIsWarning());
    }

    public function testReadEnvironmentInformation(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);
        $environmentReader = new EnvironmentDummyReader($connectionFactory);
        $environmentReader->setDummyData([]);
        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountDummyReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertSame('Shopware', $response->getSourceSystemName());
        static::assertSame('___VERSION___', $response->getSourceSystemVersion());
        static::assertSame('foo', $response->getSourceSystemDomain());
    }

    public function testReadEnvironmentInformationWithoutSourceDefaultLanguage(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);
        $environmentReader = new EnvironmentDummyReader($connectionFactory);
        $environmentReader->setDummyData(['defaultShopLanguage' => null]);
        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountDummyReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());
        static::assertInstanceOf(EnvironmentInformation::class, $response);

        static::assertSame('Shopware', $response->getSourceSystemName());
        static::assertSame('___VERSION___', $response->getSourceSystemVersion());
        static::assertSame('foo', $response->getSourceSystemDomain());
        static::assertSame('en-GB', $response->getSourceSystemLocale());
    }

    public function testGenerateFingerprintWithConfig(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);

        $environmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $environmentReader->method('read')->willReturn([
            'environmentInformation' => [
                'defaultShopLanguage' => 'de-DE',
                'defaultCurrency' => 'EUR',
                'shopwareVersion' => '5.7.0',
                'additionalData' => [],
                'config' => [
                    'esdKey' => 'test-esd-key',
                    'installationDate' => '2023-01-01 00:00:00',
                ],
            ],
            'requestStatus' => new RequestStatusStruct(),
        ]);

        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountDummyReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );

        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNotNull($response->getFingerprint());
        static::assertIsString($response->getFingerprint());
    }

    public function testGenerateFingerprintWithoutConfig(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);

        $environmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $environmentReader->method('read')->willReturn([
            'environmentInformation' => [
                'defaultShopLanguage' => 'de-DE',
                'defaultCurrency' => 'EUR',
                'shopwareVersion' => '5.7.0',
                'additionalData' => [],
            ],
            'requestStatus' => new RequestStatusStruct(),
        ]);

        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountDummyReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );

        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNull($response->getFingerprint());
    }

    public function testGenerateFingerprintWithoutInstallationDate(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ProductReader($connectionFactory);

        $environmentReader = $this->createMock(EnvironmentReaderInterface::class);
        $environmentReader->method('read')->willReturn([
            'environmentInformation' => [
                'defaultShopLanguage' => 'de-DE',
                'defaultCurrency' => 'EUR',
                'shopwareVersion' => '5.7.0',
                'additionalData' => [],
                'config' => [
                    'esdKey' => 'test-esd-key',
                ],
            ],
            'requestStatus' => new RequestStatusStruct(),
        ]);

        $tableReader = new TableReader($connectionFactory);
        $tableCountReader = new TableCountDummyReader($connectionFactory, new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            new ReaderRegistry([$apiReader]),
            $environmentReader,
            $tableReader,
            $tableCountReader,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository')
        );

        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());

        static::assertNull($response->getFingerprint());
    }
}
