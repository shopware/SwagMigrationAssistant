<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationNext\Exception\GatewayNotFoundException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use Symfony\Component\HttpFoundation\Response;

class GatewayServiceTest extends TestCase
{
    /**
     * @var GatewayFactoryRegistryInterface
     */
    private $gatewayFactoryRegistry;

    protected function setUp(): void
    {
        $this->gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([new DummyLocalFactory()]));
    }

    public function testGetGatewayNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $profile = new SwagMigrationProfileEntity();
        $profile->setName('foobar');
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $connection->setProfile($profile);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $connection,
            '',
            new ProductDataSet(),
            0,
            250
        );

        try {
            $this->gatewayFactoryRegistry->createGateway($migrationContext);
        } catch (\Exception $e) {
            /* @var GatewayNotFoundException $e */
            static::assertInstanceOf(GatewayNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
