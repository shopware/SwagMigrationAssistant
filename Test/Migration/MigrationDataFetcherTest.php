<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Service\MigrationDataFetcher;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;

class MigrationDataFetcherTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $dummyDataFetcher;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    protected function setUp()
    {
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext()
        );

        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->loggingService = new DummyLoggingService();
        $this->dummyDataFetcher = new MigrationDataFetcher(
            new ProfileRegistry(new DummyCollection([
                new Shopware55Profile(
                    $this->migrationDataRepo,
                    new ConverterRegistry([
                        $this->getContainer()->get(ProductConverter::class),
                    ]),
                    $this->getContainer()->get(MediaFileService::class),
                    new DummyLoggingService()
                ),
            ])),

            new GatewayFactoryRegistry(new DummyCollection([
                new Shopware55ApiFactory(),
                new DummyLocalFactory(),
            ])),

            $this->loggingService
        );
        $this->productRepo = $this->getContainer()->get('product.repository');
    }

    public function testFetchAssetDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
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

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', MediaDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(23, $result->getTotal());
    }

    public function testFetchCategoryDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
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

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', CategoryDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(8, $result->getTotal());
    }

    public function testFetchTranslationDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
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

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', 'translation'));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(5, $result->getTotal());
    }

    public function testFetchCustomerDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
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

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', CustomerDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(3, $result->getTotal());
    }

    public function testFetchProductDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
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

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(37, $result->getTotal());
    }

    public function testFetchProductDataLocalGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertSame(37, $result->getTotal());
    }

    public function testFetchInvalidCustomerData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName() . 'Invalid',
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $result = $this->loggingRepo->search(new Criteria(), $context);

        self::assertSame(5, $result->getTotal());

        $countValidLogging = 0;
        $countInvalidLogging = 0;

        /** @var SwagMigrationLoggingEntity $log */
        foreach ($result->getElements() as $log) {
            $type = $log->getType();
            $logEntry = $log->getLogEntry();

            if (
                ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'Empty necessary data fields for address') ||
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

        $failureConvertCriteria = new Criteria();
        $failureConvertCriteria->addFilter(new EqualsFilter('convertFailure', true));
        $result = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        self::assertSame(2, $result->getTotal());
    }

    public function testFetchWithUnknownConverter(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            'order',
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->dummyDataFetcher->fetchData($migrationContext, $context);
        $logs = $this->loggingService->getLoggingArray();
        self::assertCount(1, $logs);
        self::assertSame(LogType::CONVERTER_NOT_FOUND, $logs[0]['logEntry']['code']);
    }

    public function testGetEntityTotal(): void
    {
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));

        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationDataFetcher->getEntityTotal($migrationContext);

        self::assertSame(2, $total);

        $migrationContext = new MigrationContext(
            '',
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationDataFetcher->getEntityTotal($migrationContext);

        self::assertSame(37, $total);

        $migrationContext = new MigrationContext(
            '',
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationDataFetcher->getEntityTotal($migrationContext);

        self::assertSame(8, $total);

        $migrationContext = new MigrationContext(
            '',
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationDataFetcher->getEntityTotal($migrationContext);

        self::assertSame(23, $total);
    }
}
