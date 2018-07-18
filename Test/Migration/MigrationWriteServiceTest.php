<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationCollectService;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Migration\Writer\WriterNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class MigrationWriteServiceTest extends KernelTestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepository
     */
    private $productRepro;

    /**
     * @var MigrationCollectService
     */
    private $migrationService;

    /**
     * @var MigrationWriteService
     */
    private $migrationWriteService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        /* @var Connection $connection */
        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationService = self::$container->get(MigrationCollectService::class);
        $this->migrationWriteService = self::$container->get(MigrationWriteService::class);
        $this->productRepro = self::$container->get('product.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testWriteData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [
                'dbHost' => 'foo',
                'dbName' => 'foo',
                'dbUser' => 'foo',
                'dbPassword' => 'foo',
            ]
        );

        $this->migrationService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepro->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepro->search($criteria, $context)->getTotal();

        /** @var EntitySearchResult $result */
        self::assertEquals(37, $productTotalAfter - $productTotalBefore);
    }

    public function testWriterNotFound(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [
                'dbHost' => 'foo',
                'dbName' => 'foo',
                'dbUser' => 'foo',
                'dbPassword' => 'foo',
            ]
        );

        $this->migrationService->fetchData($migrationContext, $context);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            'foobar',
            []
        );

        try {
            $this->migrationWriteService->writeData($migrationContext, $context);
        } catch (Exception $e) {
            /* @var WriterNotFoundException $e */
            self::assertInstanceOf(WriterNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
