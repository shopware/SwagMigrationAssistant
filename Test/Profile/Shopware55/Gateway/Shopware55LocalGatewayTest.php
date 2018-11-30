<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use Doctrine\DBAL\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalFactory;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalGatewayTest extends TestCase
{
    public function testReadFailedNoCredentials(): void
    {
        $migrationContext = new MigrationContext(
            '',
            '',
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE,
            'product',
            0,
            0,
            [
                'dbName' => '',
                'dbUser' => '',
                'dbPassword' => '',
                'dbHost' => '',
                'dbPort' => '',
            ]
        );

        $factory = new Shopware55LocalFactory();
        $gatewayFactoryRegistry = new GatewayFactoryRegistry([
            $factory,
        ]);
        $gateway = $gatewayFactoryRegistry->createGateway($migrationContext);

        $this->expectException(ConnectionException::class);
        $gateway->read();
    }

    public function testReadWithUnknownEntityThrowsException(): void
    {
        $migrationContext = new MigrationContext(
            '',
            '',
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE,
            'foo',
            0,
            0,
            [
                'dbName' => '',
                'dbUser' => '',
                'dbPassword' => '',
                'dbHost' => '',
                'dbPort' => '',
            ]
        );

        $factory = new Shopware55LocalFactory();
        $gatewayFactoryRegistry = new GatewayFactoryRegistry([
            $factory,
        ]);

        $gateway = $gatewayFactoryRegistry->createGateway($migrationContext);

        $this->expectException(Shopware55LocalReaderNotFoundException::class);
        $gateway->read();
    }

    public function testReadEnvironmentInformationHasEmptyResult(): void
    {
        $migrationContext = new MigrationContext(
            '',
            '',
            '',
            '',
            '',
            0,
            0
        );

        $factory = new Shopware55LocalFactory();
        $gateway = $factory->create($migrationContext);
        $response = $gateway->readEnvironmentInformation();

        $this->assertInstanceOf(EnvironmentInformation::class, $response);
        $this->assertSame($response->getProductTotal(), 0);
    }
}
