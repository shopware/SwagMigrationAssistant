<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Driver\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\AssetDownloadService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssetDownloadServiceTest extends KernelTestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AssetDownloadService
     */
    private $assetDownloadService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->assetDownloadService = self::$container->get(AssetDownloadService::class);
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testDownloadAssets()
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $this->assetDownloadService->downloadAssets($context);
        //todo: Create a good test
    }
}
