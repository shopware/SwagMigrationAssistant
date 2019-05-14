<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
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
