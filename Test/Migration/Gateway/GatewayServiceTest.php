<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use Symfony\Component\HttpFoundation\Response;

class GatewayServiceTest extends TestCase
{
    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    protected function setUp(): void
    {
        $this->gatewayRegistry = new GatewayRegistry(new DummyCollection([new DummyLocalGateway()]));
    }

    public function testGetGatewayNotFound(): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setProfileName('foobar');
        $connection->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            $connection,
            '',
            new ProductDataSet(),
            0,
            250
        );

        try {
            $this->gatewayRegistry->getGateway($migrationContext);
        } catch (\Exception $e) {
            /* @var GatewayNotFoundException $e */
            static::assertInstanceOf(GatewayNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
