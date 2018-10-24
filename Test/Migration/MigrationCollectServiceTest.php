<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingStruct;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;

class MigrationCollectServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    /**
     * @var RepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var string
     */
    private $runUuid;

    protected function setUp()
    {
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profile' => Shopware55Profile::PROFILE_NAME,
                ],
            ],
            Context::createDefaultContext(Defaults::TENANT_ID)
        );

        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->migrationDataRepo,
            $this->loggingRepo,
            $this->getContainer()->get(Shopware55MappingService::class)
        );
        $this->productRepo = $this->getContainer()->get('product.repository');
    }

    public function testFetchAssetDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', MediaDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(23, $result->getTotal());
    }

    public function testFetchCategoryDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', CategoryDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(8, $result->getTotal());
    }

    public function testFetchTranslationDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            'translation',
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', 'translation'));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(5, $result->getTotal());
    }

    public function testFetchCustomerDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', CustomerDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(3, $result->getTotal());
    }

    public function testFetchProductDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(37, $result->getTotal());
    }

    public function testFetchProductDataLocalGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $this->runUuid));
        $criteria->addFilter(new TermQuery('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(37, $result->getTotal());
    }

    public function testFetchInvalidCustomerData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName() . 'Invalid',
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $result = $this->loggingRepo->search(new Criteria(), $context);

        self::assertSame(5, $result->getTotal());

        $countValidLogging = 0;
        $countInvalidLogging = 0;

        /** @var SwagMigrationLoggingStruct $log */
        foreach ($result->getElements() as $log) {
            $type = $log->getType();
            $logEntry = $log->getLogEntry();

            if (
                ($type === LoggingService::WARNING_TYPE && $logEntry['title'] === 'Empty necessary data fields for address') ||
                ($type === LoggingService::WARNING_TYPE && $logEntry['title'] === 'Empty necessary data fields') ||
                ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'No default shipping address') ||
                ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'No default billing and shipping address') ||
                ($type === LoggingService::WARNING_TYPE && $logEntry['title'] === 'No address data')
            ) {
                ++$countValidLogging;
                continue;
            }

            ++$countInvalidLogging;
        }

        self::assertSame(5, $countValidLogging);
        self::assertSame(0, $countInvalidLogging);
    }
}
