<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use SwagMigrationAssistant\Test\Mock\Migration\Service\DummyMigrationDataFetcher;

#[Package('services-settings')]
class RunServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationServicesTrait;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>
     */
    private EntityRepository $connectionRepo;

    /**
     * @var EntityRepository<SwagMigrationMappingCollection>
     */
    private EntityRepository $mappingRepo;

    /**
     * @var EntityRepository<SwagMigrationDataCollection>
     */
    private EntityRepository $dataRepo;

    private RunService $runServiceWithoutStructure;

    private SwagMigrationConnectionEntity $connection;

    private MigrationContextFactoryInterface $migrationContextFactory;

    protected function setUp(): void
    {
        $entityWriter = static::getContainer()->get(EntityWriter::class);
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = static::getContainer()->get('swag_migration_data.repository');
        $this->connectionRepo = static::getContainer()->get('swag_migration_connection.repository');
        $this->mappingRepo = static::getContainer()->get('swag_migration_mapping.repository');
        $loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $mediaFileRepo = static::getContainer()->get('swag_migration_media_file.repository');
        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');
        $themeRepo = static::getContainer()->get('theme.repository');
        $this->migrationContextFactory = static::getContainer()->get(MigrationContextFactory::class);

        $mappingService = new MappingService(
            $this->mappingRepo,
            static::getContainer()->get('locale.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get('country.repository'),
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('tax.repository'),
            static::getContainer()->get('number_range.repository'),
            static::getContainer()->get('rule.repository'),
            static::getContainer()->get('media_thumbnail_size.repository'),
            static::getContainer()->get('media_default_folder.repository'),
            static::getContainer()->get('category.repository'),
            static::getContainer()->get('cms_page.repository'),
            static::getContainer()->get('delivery_time.repository'),
            static::getContainer()->get('document_type.repository'),
            $entityWriter,
            static::getContainer()->get(SwagMigrationMappingDefinition::class)
        );
        $loggingService = new LoggingService($loggingRepo);

        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionId): void {
            $this->connectionRepo->create(
                [
                    [
                        'id' => $connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );
        });
        $connection = $this->connectionRepo->search(new Criteria([$connectionId]), $context)->first();

        static::assertInstanceOf(SwagMigrationConnectionEntity::class, $connection);

        $this->connection = $connection;

        $connectionFactory = new ConnectionFactory();
        $gatewayRegistry = new GatewayRegistry(new DummyCollection([
            new ShopwareApiGateway(
                static::getContainer()->get(ReaderRegistry::class),
                new EnvironmentReader($connectionFactory),
                new TableReader($connectionFactory),
                new TableCountReader($connectionFactory, $loggingService),
                static::getContainer()->get('currency.repository'),
                static::getContainer()->get('language.repository')
            ),
            new DummyLocalGateway(),
        ]));

        $this->runServiceWithoutStructure = new RunService(
            $this->runRepo,
            $this->connectionRepo,
            new DummyMigrationDataFetcher(
                $gatewayRegistry,
                $loggingService
            ),
            new SwagMigrationAccessTokenService($this->runRepo),
            new DataSelectionRegistry([]),
            $this->dataRepo,
            $mediaFileRepo,
            $salesChannelRepo,
            $themeRepo,
            static::getContainer()->get(EntityIndexerRegistry::class),
            static::getContainer()->get(ThemeService::class),
            $mappingService,
            static::getContainer()->get('cache.object'),
            static::getContainer()->get(SwagMigrationDataDefinition::class),
            static::getContainer()->get(Connection::class),
            $loggingService,
            static::getContainer()->get(TrackingEventClient::class),
            static::getContainer()->get('messenger.bus.shopware')
        );
    }

    public function testCreateMigrationRunWithoutStructure(): void
    {
        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $origin->setIsAdmin(true);
        $context = Context::createDefaultContext($origin);

        $runId = Uuid::randomHex();
        $this->runRepo->create([
            [
                'id' => $runId,
                'connectionId' => $this->connection->getId(),
                'status' => SwagMigrationRunEntity::STATUS_FINISHED,
            ],
        ], $context);

        $this->dataRepo->create([
            [
                'id' => Uuid::randomHex(),
                'runId' => $runId,
                'entity' => 'product',
                'raw' => ['id' => 'testId'],
                'written' => false,
            ],
        ], $context);

        $migrationContext = $this->migrationContextFactory->createByConnection($this->connection);

        $beforeRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $beforeMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();
        $this->runServiceWithoutStructure->createMigrationRun(
            $migrationContext,
            [],
            $context
        );
        $afterRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $afterMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();

        static::assertSame(1, $afterRunTotal - $beforeRunTotal);
        static::assertSame(0, $afterMappingTotal - $beforeMappingTotal);

        $data = $this->dataRepo->search(new Criteria(), $context)->getTotal();
        static::assertSame(0, $data);
    }
}
