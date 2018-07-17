<?php declare(strict_types=1);


namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\EntityRepository;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationService;
use SwagMigrationNext\Migration\MigrationValidateService;
use SwagMigrationNext\Migration\Validator\ValidatorNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class MigrationValidateServiceTest extends KernelTestCase
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
     * @var MigrationValidateService
     */
    private $migrationValidateService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        /* @var Connection $connection */
        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationService = self::$container->get(MigrationService::class);
        $this->migrationValidateService = self::$container->get(MigrationValidateService::class);
        $this->productRepro = self::$container->get('product.repository');
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
        $this->migrationValidateService->validateData($migrationContext, $context);
        //Todo: Implement a real test
        self::assertTrue(true);
    }

    public function testValidatorNotFound(): void
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
            $this->migrationValidateService->validateData($migrationContext, $context);
        } catch (\Exception $e) {
            /* @var ValidatorNotFoundException $e */
            self::assertInstanceOf(ValidatorNotFoundException::class, $e);
            self::assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}