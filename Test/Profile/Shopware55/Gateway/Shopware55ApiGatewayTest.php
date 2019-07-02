<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiTableCountReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiTableReader;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Test\Profile\Shopware55\DataSet\FooDataSet;

class Shopware55ApiGatewayTest extends TestCase
{
    use KernelTestBehaviour;

    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            '',
            new FooDataSet()
        );

        $this->expectException(GatewayReadException::class);

        $connectionFactory = new ConnectionFactory();
        $apiReader = new Shopware55ApiReader($connectionFactory);
        $environmentReader = new Shopware55ApiEnvironmentReader($connectionFactory);
        $tableReader = new Shopware55ApiTableReader($connectionFactory);
        $tableCountReader = new Shopware55ApiTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class));

        $gateway = new Shopware55ApiGateway(
            $apiReader,
            $environmentReader,
            $tableReader,
            $tableCountReader
        );
        $gateway->read($migrationContext);
    }

    public function testReadEnvironmentInformationFailed(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity()
        );

        $connectionFactory = new ConnectionFactory();
        $apiReader = new Shopware55ApiReader($connectionFactory);
        $environmentReader = new Shopware55ApiEnvironmentReader($connectionFactory);
        $tableReader = new Shopware55ApiTableReader($connectionFactory);
        $tableCountReader = new Shopware55ApiTableCountReader($connectionFactory, $this->getContainer()->get(DataSetRegistry::class));

        $gateway = new Shopware55ApiGateway(
            $apiReader,
            $environmentReader,
            $tableReader,
            $tableCountReader
        );
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation($migrationContext);
        $errorException = new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);

        static::assertSame($response->getTotals(), []);
        static::assertSame($response->getRequestStatus()->getCode(), $errorException->getErrorCode());
        static::assertSame($response->getRequestStatus()->getMessage(), $errorException->getMessage());
        static::assertFalse($response->getRequestStatus()->getIsWarning());
    }
}
