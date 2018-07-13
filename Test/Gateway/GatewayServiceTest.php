<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Gateway;

use Exception;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Gateway\GatewayNotFoundException;
use SwagMigrationNext\Migration\MigrationContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class GatewayServiceTest extends KernelTestCase
{
    /**
     * @var GatewayFactoryRegistry
     */
    private $gatewayService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->gatewayService = self::$container->get(GatewayFactoryRegistry::class);
    }

    public function testGetGatewayNotFound(): void
    {
        $migrationContext = new MigrationContext(
            'foobar',
            ProductDefinition::getEntityName(),
            'api',
            [
                'endpoint' => 'foo',
                'apiUser' => 'foo',
                'apiKey' => 'foo',
            ]
        );

        try {
            $this->gatewayService->createGateway($migrationContext);
        } catch (Exception $e) {
            /* @var GatewayNotFoundException $e */
            self::assertInstanceOf(GatewayNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
