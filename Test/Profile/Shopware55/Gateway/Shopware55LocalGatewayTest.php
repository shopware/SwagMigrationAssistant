<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use Doctrine\DBAL\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalFactory;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Profile\Shopware55\DataSet\FooDataSet;

class Shopware55LocalGatewayTest extends TestCase
{
    public function testReadFailedNoCredentials(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $connection->setProfile($profile);
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
            '',
            new ProductDataSet()
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
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $connection->setProfile($profile);
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
            '',
            new FooDataSet()
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
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();

        $connection->setProfile($profile);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $connection
        );

        $factory = new Shopware55LocalFactory();
        $gateway = $factory->create($migrationContext);
        $response = $gateway->readEnvironmentInformation();

        static::assertSame($response->getTotals(), []);
    }
}
