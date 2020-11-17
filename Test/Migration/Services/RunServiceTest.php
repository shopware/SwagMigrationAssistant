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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\RunService;
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
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use SwagMigrationAssistant\Test\Mock\Migration\Service\DummyMigrationDataFetcher;

class RunServiceTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $dataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mappingRepo;

    /**
     * @var MappingService
     */
    private $mappingService;

    /**
     * @var RunService
     */
    private $runServiceWithoutStructure;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeRepo;

    protected function setUp(): void
    {
        $entityWriter = $this->getContainer()->get(EntityWriter::class);
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');
        $loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $this->themeRepo = $this->getContainer()->get('theme.repository');
        $this->migrationContextFactory = $this->getContainer()->get(MigrationContextFactory::class);

        $this->mappingService = new MappingService(
            $this->mappingRepo,
            $this->getContainer()->get('locale.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $this->getContainer()->get('category.repository'),
            $this->getContainer()->get('cms_page.repository'),
            $this->getContainer()->get('delivery_time.repository'),
            $this->getContainer()->get('document_type.repository'),
            $entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );
        $loggingService = new LoggingService($loggingRepo);
        $mediaFileService = new DummyMediaFileService();

        $connectionId = Uuid::randomHex();
        $context = $context = Context::createDefaultContext();
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
        $this->connection = $this->connectionRepo->search(new Criteria([$connectionId]), $context)->first();

        $connectionFactory = new ConnectionFactory();
        $gatewayRegistry = new GatewayRegistry(new DummyCollection([
            new ShopwareApiGateway(
                $this->getContainer()->get(ReaderRegistry::class),
                new EnvironmentReader($connectionFactory),
                new TableReader($connectionFactory),
                new TableCountReader($connectionFactory, $loggingService),
                $this->getContainer()->get('currency.repository'),
                $this->getContainer()->get('language.repository')
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
            $this->salesChannelRepo,
            $this->themeRepo,
            $this->getContainer()->get(EntityIndexerRegistry::class),
            $this->getContainer()->get(ThemeService::class),
            $this->mappingService,
            $this->getContainer()->get('cache.object'),
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(Connection::class),
            $loggingService,
            $this->getContainer()->get(StoreService::class),
            $this->getContainer()->get('messenger.bus.shopware')
        );
    }

    public function testCreateMigrationRunWithoutStructure(): void
    {
        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $origin->setIsAdmin(true);
        $context = Context::createDefaultContext($origin);

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
    }
}
