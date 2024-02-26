<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationProgressStatus;
use SwagMigrationAssistant\Migration\Run\ProgressDataSet;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingDefinition;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class RunServiceTest extends TestCase
{
    /**
     * @var StaticEntityRepository<GeneralSettingCollection>
     */
    private StaticEntityRepository $generalSettingRepo;

    /**
     * @var StaticEntityRepository<SwagMigrationConnectionCollection>
     */
    private StaticEntityRepository $connectionRepo;

    /**
     * @var StaticEntityRepository<SwagMigrationRunCollection>
     */
    private StaticEntityRepository $runRepo;

    private MigrationContextFactory&MockObject $migrationContextFactory;

    private MigrationDataFetcher&MockObject $dataFetcher;

    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $generalSettingEntity = new GeneralSettingEntity();
        $generalSettingEntity->setId(Uuid::randomHex());
        $generalSettingEntity->setSelectedConnectionId(Uuid::randomHex());

        $this->generalSettingRepo = new StaticEntityRepository([
            new GeneralSettingCollection([$generalSettingEntity]),
        ], new GeneralSettingDefinition());

        $connectionEntity = new SwagMigrationConnectionEntity();
        $connectionEntity->setId(Uuid::randomHex());
        $connectionEntity->setProfileName(Shopware55Profile::PROFILE_NAME);
        $connectionEntity->setGatewayName(ShopwareLocalGateway::GATEWAY_NAME);

        $this->connectionRepo = new StaticEntityRepository([
            new SwagMigrationConnectionCollection([$connectionEntity]),
        ], new SwagMigrationConnectionDefinition());

        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setConnection($connectionEntity);
        $run->setStatus(SwagMigrationRunEntity::STATUS_RUNNING);

        $progress = new MigrationProgress(
            MigrationProgressStatus::WAITING_FOR_APPROVE,
            0,
            10,
            new ProgressDataSetCollection([new ProgressDataSet('product', 10)]),
            'product',
            0
        );

        $run->setProgress($progress);

        $this->runRepo = new StaticEntityRepository([
            [],
            [],
            new SwagMigrationRunCollection([$run]),
        ], new SwagMigrationRunDefinition());

        $this->migrationContextFactory = $this->createMock(MigrationContextFactory::class);
        $this->migrationContextFactory->method('createByConnection')->willReturn(new MigrationContext(
            new Shopware55Profile(),
            $connectionEntity,
        ));

        $this->dataFetcher = $this->createMock(MigrationDataFetcher::class);
        $this->dataFetcher->method('getEnvironmentInformation')->willReturn(new EnvironmentInformation(
            'Source System',
            '1.0.0',
            'Shopware',
            ['product' => new TotalStruct('product', 10)],
        ));
    }

    public function testStartMigrationRunSuccessfully(): void
    {
        $trackingEventClient = $this->createMock(TrackingEventClient::class);
        $trackingEventClient
            ->expects(static::once())
            ->method('fireTrackingEvent');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function (MigrationProcessMessage $message): bool {
                static::assertSame($message->getContext(), $this->context);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $runService = $this->createRunService(
            $trackingEventClient,
            $messageBus,
            $this->createMock(PremappingService::class)
        );

        $runService->startMigrationRun([ProductDataSelection::IDENTIFIER], $this->context);

        static::assertCount(1, $this->runRepo->creates);
        static::assertCount(1, $this->runRepo->updates);
        static::assertCount(0, $this->runRepo->deletes);
    }

    public function testStartMigrationRunWithRunningMigration(): void
    {
        $trackingEventClient = $this->createMock(TrackingEventClient::class);
        $trackingEventClient
            ->expects(static::never())
            ->method('fireTrackingEvent');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->runRepo = new StaticEntityRepository([
            [Uuid::randomHex()],
        ], new SwagMigrationRunDefinition());

        $runService = $this->createRunService(
            $trackingEventClient,
            $messageBus,
            $this->createMock(PremappingService::class)
        );

        try {
            $runService->startMigrationRun([ProductDataSelection::IDENTIFIER], $this->context);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::MIGRATION_IS_ALREADY_RUNNING, $exception->getErrorCode());
        }
    }

    public function testStartMigrationRunWithNoSelectedConnection(): void
    {
        $trackingEventClient = $this->createMock(TrackingEventClient::class);
        $trackingEventClient
            ->expects(static::never())
            ->method('fireTrackingEvent');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->runRepo = new StaticEntityRepository([
            [],
        ], new SwagMigrationRunDefinition());

        $generalSettingEntity = new GeneralSettingEntity();
        $generalSettingEntity->setId(Uuid::randomHex());
        $generalSettingEntity->assign(['selectedConnectionId' => null]);

        $this->generalSettingRepo = new StaticEntityRepository(
            [
                new GeneralSettingCollection([
                    $generalSettingEntity,
                ]),
            ],
            new GeneralSettingDefinition()
        );

        $runService = $this->createRunService(
            $trackingEventClient,
            $messageBus,
            $this->createMock(PremappingService::class)
        );

        try {
            $runService->startMigrationRun([ProductDataSelection::IDENTIFIER], $this->context);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::NO_CONNECTION_IS_SELECTED, $exception->getErrorCode());
        }
    }

    public function testStartMigrationRunWithInvalidPremapping(): void
    {
        $trackingEventClient = $this->createMock(TrackingEventClient::class);
        $trackingEventClient
            ->expects(static::never())
            ->method('fireTrackingEvent');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects(static::never())
            ->method('dispatch');

        $premappingService = $this->createMock(PremappingService::class);
        $premappingService->expects(static::once())->method('generatePremapping')->willReturn([
            new PremappingStruct('salutation', [
                new PremappingEntityStruct('salutation', 'salutation', ''),
            ], []),
        ]);

        $runService = $this->createRunService(
            $trackingEventClient,
            $messageBus,
            $premappingService
        );

        try {
            $runService->startMigrationRun([ProductDataSelection::IDENTIFIER], $this->context);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::PREMAPPING_IS_INCOMPLETE, $exception->getErrorCode());
        }
    }

    private function createRunService(
        MockObject&TrackingEventClient $trackingEventClient,
        MockObject&MessageBusInterface $messageBus,
        MockObject&PremappingService $premappingService
    ): RunService {
        /** @var StaticEntityRepository<SwagMigrationDataCollection> $migrationDataRepository */
        $migrationDataRepository = new StaticEntityRepository([]);
        /** @var StaticEntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepository */
        $mediaFileRepository = new StaticEntityRepository([]);
        /** @var StaticEntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = new StaticEntityRepository([]);
        /** @var StaticEntityRepository<ThemeCollection> $themeRepository */
        $themeRepository = new StaticEntityRepository([]);

        return new RunService(
            $this->runRepo,
            $this->connectionRepo,
            $this->dataFetcher,
            new DataSelectionRegistry([
                new ProductDataSelection(),
            ]),
            $salesChannelRepository,
            $themeRepository,
            $this->generalSettingRepo,
            $this->createMock(ThemeService::class),
            $this->createMock(MappingService::class),
            new SwagMigrationDataDefinition(),
            $this->createMock(Connection::class),
            $this->createMock(LoggingService::class),
            $trackingEventClient,
            $messageBus,
            $this->migrationContextFactory,
            $premappingService
        );
    }
}
