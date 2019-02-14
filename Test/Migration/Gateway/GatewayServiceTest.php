<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Gateway;

use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Exception\GatewayNotFoundException;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\MigrationContext;
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
        $migrationContext = new MigrationContext(
            '',
            '',
            'foobar',
            'api',
            ProductDefinition::getEntityName(),
            [
                'endpoint' => 'foo',
                'apiUser' => 'foo',
                'apiKey' => 'foo',
            ],
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
