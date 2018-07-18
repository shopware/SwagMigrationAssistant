<?php

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
use SwagMigrationNext\Migration\MigrationService;
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
     * @var MigrationService
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

        $this->migrationService = self::$container->get(MigrationService::class);
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
            ProductDefinition::getEntityName(),
            'local',
            [
                'dbHost' => 'foo',
                'dbName' => 'foo',
                'dbUser' => 'foo',
                'dbPassword' => 'foo',
            ]
        );

        $this->migrationService->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);

        $criteria = new Criteria();
        /** @var EntitySearchResult $result */
        $result = $this->productRepro->search($criteria, $context);
        self::assertEquals(1062, $result->getTotal());
    }

    public function testWriterNotFound(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            ProductDefinition::getEntityName(),
            'local',
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
            'foobar',
            'local',
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
