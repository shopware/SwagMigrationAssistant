<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware\Gateway;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ApiReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ApiTableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ApiTableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader\ApiEnvironmentDummyReader;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Api\Reader\ApiTableCountDummyReader;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Profile\Shopware\DataSet\FooDataSet;

class ShopwareApiGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            new SwagMigrationConnectionEntity(),
            '',
            new FooDataSet()
        );

        $this->expectException(GatewayReadException::class);

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ApiReader($connectionFactory);
        $environmentReader = new ApiEnvironmentReader($connectionFactory);
        $tableReader = new ApiTableReader($connectionFactory);
        $tableCountReader = new ApiTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            $apiReader,
            $environmentReader,
            $tableReader,
            $tableCountReader,
            $this->getContainer()->get('currency.repository')
        );
        $gateway->read($migrationContext);
    }

    public function testReadEnvironmentInformationFailed(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            new SwagMigrationConnectionEntity()
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ApiReader($connectionFactory);
        $environmentReader = new ApiEnvironmentReader($connectionFactory);
        $tableReader = new ApiTableReader($connectionFactory);
        $tableCountReader = new ApiTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            $apiReader,
            $environmentReader,
            $tableReader,
            $tableCountReader,
            $this->getContainer()->get('currency.repository')
        );
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());
        $errorException = new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);

        static::assertSame($response->getTotals(), []);
        static::assertSame($response->getRequestStatus()->getCode(), $errorException->getErrorCode());
        static::assertSame($response->getRequestStatus()->getMessage(), $errorException->getMessage());
        static::assertFalse($response->getRequestStatus()->getIsWarning());
    }

    public function testReadEnvironmentInformation(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setCredentialFields(['endpoint' => 'foo']);

        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $connection
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new ApiReader($connectionFactory);
        $environmentReader = new ApiEnvironmentDummyReader($connectionFactory);
        $tableReader = new ApiTableReader($connectionFactory);
        $tableCountReader = new ApiTableCountDummyReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class), new DummyLoggingService());

        $gateway = new ShopwareApiGateway(
            $apiReader,
            $environmentReader,
            $tableReader,
            $tableCountReader,
            $this->getContainer()->get('currency.repository')
        );
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation($migrationContext, Context::createDefaultContext());
        static::assertInstanceOf(EnvironmentInformation::class, $response);

        static::assertSame('Shopware', $response->getSourceSystemName());
        static::assertSame('___VERSION___', $response->getSourceSystemVersion());
        static::assertSame('foo', $response->getSourceSystemDomain());
    }
}
