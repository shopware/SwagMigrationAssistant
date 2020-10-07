<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Gateway;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use SwagMigrationAssistant\Test\Mock\Profile\Dummy\DummyProfile;
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
        $connection->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);
        $connection->setCredentialFields([]);

        $migrationContext = new MigrationContext(
            new DummyProfile(),
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
