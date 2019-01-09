<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader\Shopware55LocalReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalFactory;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Response;

class Shopware55LocalGatewayTest extends TestCase
{
    public function testReadFailed(): void
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
        $response = $gateway->read();
        $this->assertSame($response, []);
    }

    public function testReadWithReaderNotFound(): void
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

        try {
            $gateway = $gatewayFactoryRegistry->createGateway($migrationContext);
            $gateway->read();
        } catch (\Exception $e) {
            /* @var Shopware55LocalReaderNotFoundException $e */
            self::assertInstanceOf(Shopware55LocalReaderNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            self::assertSame('Shopware55 local reader for "foo" not found', $e->getMessage());
        }
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
            0
        );

        $factory = new Shopware55LocalFactory();
        $gateway = $factory->create($migrationContext);
        $response = $gateway->readEnvironmentInformation();

        $this->assertSame($response, []);
    }
}
