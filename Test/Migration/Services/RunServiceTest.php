<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Context\AdminApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Indexing\IndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
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
    private $runService;

    /**
     * @var RunService
     */
    private $runServiceWithoutStructure;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var Connection
     */
    private $dbConnection;

    protected function setUp(): void
    {
        $this->dbConnection = $this->getContainer()->get(Connection::class);
        $entityWriter = $this->getContainer()->get(EntityWriter::class);
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');
        $loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');

        $this->mappingService = new MappingService(
            $this->mappingRepo,
            $this->getContainer()->get('locale.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get('country.repository'),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('sales_channel.repository'),
            $this->getContainer()->get('sales_channel_type.repository'),
            $this->getContainer()->get('payment_method.repository'),
            $this->getContainer()->get('shipping_method.repository'),
            $this->getContainer()->get('tax.repository'),
            $this->getContainer()->get('number_range.repository'),
            $this->getContainer()->get('rule.repository'),
            $this->getContainer()->get('media_thumbnail_size.repository'),
            $this->getContainer()->get('media_default_folder.repository'),
            $entityWriter,
            $this->getContainer()->get(SwagMigrationMappingDefinition::class)
        );
        $loggingService = new LoggingService($loggingRepo);
        $mediaFileService = new DummyMediaFileService();

        $profileUuidService = new MigrationProfileUuidService(
            $profileRepo,
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_NAME
        );

        $connectionId = Uuid::randomHex();
        $context = $context = Context::createDefaultContext();
        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionId, $profileUuidService) {
            $this->connectionRepo->create(
                [
                    [
                        'id' => $connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileId' => $profileUuidService->getProfileUuid(),
                    ],
                ],
                $context
            );
        });
        $this->connection = $this->connectionRepo->search(new Criteria([$connectionId]), $context)->first();

        $converterRegistry = new ConverterRegistry(new DummyCollection([]));

        $profileRegistry = new ProfileRegistry(new DummyCollection([
            new Shopware55Profile(
                $entityWriter,
                $converterRegistry,
                $mediaFileService,
                $loggingService,
                $this->getContainer()->get(SwagMigrationDataDefinition::class)
            ),
        ]));

        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        $this->runService = new RunService(
            $this->runRepo,
            $this->connectionRepo,
            $this->getMigrationDataFetcher(
                $entityWriter,
                $this->mappingService,
                $mediaFileService,
                $loggingRepo,
                $this->getContainer()->get(SwagMigrationDataDefinition::class)
            ),
            new SwagMigrationAccessTokenService($this->runRepo),
            new DataSelectionRegistry([]),
            $this->dataRepo,
            $mediaFileRepo,
            $this->getContainer()->get(IndexerRegistry::class),
            $this->getContainer()->get('shopware.cache')
        );

        $this->runServiceWithoutStructure = new RunService(
            $this->runRepo,
            $this->connectionRepo,
            new DummyMigrationDataFetcher(
                $profileRegistry,
                $gatewayFactoryRegistry,
                $loggingService
            ),
            new SwagMigrationAccessTokenService($this->runRepo),
            new DataSelectionRegistry([]),
            $this->dataRepo,
            $mediaFileRepo,
            $this->getContainer()->get(IndexerRegistry::class),
            $this->getContainer()->get('shopware.cache')
        );
    }

    public function testCreateMigrationRunWithoutStructure(): void
    {
        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $context = Context::createDefaultContext($origin);

        $beforeRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $beforeMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();
        $this->runServiceWithoutStructure->createMigrationRun(
            $this->connection->getId(),
            [],
            $context
        );
        $afterRunTotal = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $afterMappingTotal = $this->mappingRepo->search(new Criteria(), $context)->getTotal();

        static::assertSame(1, $afterRunTotal - $beforeRunTotal);
        static::assertSame(0, $afterMappingTotal - $beforeMappingTotal);
    }
}
