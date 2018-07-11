<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Gateway;

use SwagMigrationNext\Gateway\GatewayNotFoundException;
use SwagMigrationNext\Gateway\GatewayService;
use SwagMigrationNext\Migration\MigrationContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GatewayServiceTest extends KernelTestCase
{
    /**
     * @var GatewayService
     */
    private $gatewayService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->gatewayService = self::$container->get(GatewayService::class);
    }

    public function testGetGatewayNotFound()
    {
        $this->expectException(GatewayNotFoundException::class);
        $migrationContext = new MigrationContext(
            'foobar',
            'product',
            'api',
            [
                'endpoint' => 'foo',
                'apiUser' => 'foo',
                'apiKey' => 'foo',
            ]
        );
        $this->gatewayService->getGateway($migrationContext);
    }
}
