<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;

class Shopware55ApiGatewayTest extends TestCase
{
    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            '',
            new SwagMigrationConnectionEntity(),
            '',
            0,
            0
        );

        $this->expectException(GatewayReadException::class);
        $factory = new Shopware55ApiFactory();
        $gateway = $factory->create($migrationContext);
        $gateway->read();
    }

    public function testReadEnvironmentInformationFailed(): void
    {
        $migrationContext = new MigrationContext(
            '',
            new SwagMigrationConnectionEntity(),
            '',
            0,
            0
        );

        $factory = new Shopware55ApiFactory();
        $gateway = $factory->create($migrationContext);
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation();

        $this->assertSame($response->getTotals(), []);
        $this->assertSame($response->getErrorCode(), 0);
    }
}
