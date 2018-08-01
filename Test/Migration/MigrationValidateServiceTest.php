<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationValidateService;
use SwagMigrationNext\Migration\MigrationValidateServiceInterface;
use SwagMigrationNext\Migration\Validator\ValidatorNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class MigrationValidateServiceTest extends KernelTestCase
{
    use MigrationServicesTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationValidateServiceInterface
     */
    private $migrationValidateService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationCollectService = $this->getMigrationCollectService(
            self::$container->get('swag_migration_data.repository'),
            self::$container->get(MappingService::class)
        );
        $this->migrationValidateService = self::$container->get(MigrationValidateService::class);
        $this->productRepo = self::$container->get('product.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testValidateData(): void
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
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $this->migrationValidateService->validateData($migrationContext, $context);
        //Todo: Implement a real test
        self::assertTrue(true);
    }

    public function testValidatorNotFound(): void
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
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            'foobar',
            [],
            0,
            250
        );

        try {
            $this->migrationValidateService->validateData($migrationContext, $context);
        } catch (\Exception $e) {
            /* @var ValidatorNotFoundException $e */
            self::assertInstanceOf(ValidatorNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
