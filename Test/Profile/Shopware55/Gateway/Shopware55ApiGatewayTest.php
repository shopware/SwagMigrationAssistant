<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Test\Profile\Shopware55\DataSet\FooDataSet;

class Shopware55ApiGatewayTest extends TestCase
{
    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            '',
            new FooDataSet()
        );

        $this->expectException(GatewayReadException::class);
        $gateway = new Shopware55ApiGateway();
        $gateway->read($migrationContext);
    }

    public function testReadEnvironmentInformationFailed(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity()
        );

        $gateway = new Shopware55ApiGateway();
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation($migrationContext);
        $errorException = new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);

        static::assertSame($response->getTotals(), []);
        static::assertSame($response->getErrorCode(), $errorException->getErrorCode());
    }
}
