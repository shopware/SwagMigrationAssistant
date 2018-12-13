<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\GatewayReadException;
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
            [
                'endpoint' => '',
                'apiUser' => '',
                'apiKey' => '',
            ],
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
            '',
            '',
            '',
            '',
            [
                'endpoint' => '',
                'apiUser' => '',
                'apiKey' => '',
            ],
            0,
            0
        );

        $factory = new Shopware55ApiFactory();
        $gateway = $factory->create($migrationContext);
        $response = $gateway->readEnvironmentInformation();

        $this->assertSame($response['environmentInformation'], []);
        $this->assertSame($response['error']['code'], 0);
    }
}
