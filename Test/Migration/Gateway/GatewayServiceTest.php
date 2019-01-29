<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Gateway;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Exception\GatewayNotFoundException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
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
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_TYPE);

        $connection->setProfile($profile);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            '',
            $connection,
            ProductDefinition::getEntityName(),
            0,
            250
        );

        try {
            $this->gatewayFactoryRegistry->createGateway($migrationContext);
        } catch (Exception $e) {
            /* @var GatewayNotFoundException $e */
            self::assertInstanceOf(GatewayNotFoundException::class, $e);
            self::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
