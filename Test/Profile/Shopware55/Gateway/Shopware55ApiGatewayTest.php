<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;

class Shopware55ApiGatewayTest extends TestCase
{
    public function testReadFailed(): void
    {
        $migrationContext = new MigrationContext(
            '',
            '',
            '',
            '',
            '',
            0,
            0,
            [
                'endpoint' => '',
                'apiUser' => '',
                'apiKey' => '',
            ]
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
            '',
            '',
            '',
            '',
            0,
            0,
            [
                'endpoint' => '',
                'apiUser' => '',
                'apiKey' => '',
            ]
        );

        $factory = new Shopware55ApiFactory();
        $gateway = $factory->create($migrationContext);
        /** @var EnvironmentInformation $response */
        $response = $gateway->readEnvironmentInformation();

        $this->assertSame($response->getProductTotal(), 0);
        $this->assertSame($response->getErrorCode(), 0);
    }
}
