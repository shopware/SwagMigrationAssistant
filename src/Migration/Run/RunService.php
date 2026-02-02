<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeDefinition;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\History\LogGroupingService;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\WriteThemeCompilingFailedLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\TruncateMigrationMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('fundamentals@after-sales')]
class RunService implements RunServiceInterface
{
    private const TRACKING_EVENT_MIGRATION_STARTED = 'Migration started';
    private const TRACKING_EVENT_MIGRATION_FINISHED = 'Migration finished';
    private const TRACKING_EVENT_MIGRATION_ABORTED = 'Migration aborted';

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepo
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<ThemeCollection> $themeRepository
     * @param EntityRepository<GeneralSettingCollection> $generalSettingRepo
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
        private readonly EntityRepository $connectionRepo,
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly DataSelectionRegistryInterface $dataSelectionRegistry,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $themeRepository,
        private readonly EntityRepository $generalSettingRepo,
        private readonly ThemeService $themeService,
        private readonly MappingServiceInterface $mappingService,
        private readonly Connection $dbalConnection,
        private readonly LoggingServiceInterface $loggingService,
        private readonly TrackingEventClient $trackingEventClient,
        private readonly MessageBusInterface $bus,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly PremappingServiceInterface $premappingService,
        private readonly RunTransitionServiceInterface $runTransitionService,
        private readonly LogGroupingService $logGroupingService,
    ) {
    }

    public function startMigrationRun(array $dataSelectionIds, Context $context): void
    {
        if ($this->isMigrationRunning($context)) {
            throw MigrationException::migrationProcessing();
        }

        if ($this->isResettingChecksums()) {
            throw MigrationException::migrationProcessing('checksum reset');
        }

        if ($this->isTruncatingMigrationData()) {
            throw MigrationException::migrationProcessing('data truncation');
        }

        $connection = $this->getCurrentConnection($context);

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $environmentInformation = $this->getEnvironmentInformation($migrationContext, $context);

        if ($environmentInformation->isMigrationDisabled()) {
            throw MigrationException::migrationDisabledBySource();
        }

        if (empty($dataSelectionIds)) {
            throw MigrationException::noDataToMigrate();
        }

        if (!$this->isPremappingValid($dataSelectionIds, $connection, $context)) {
            throw MigrationException::premappingIsIncomplete();
        }

        $connectionId = $connection->getId();
        $runUuid = $this->createPlainMigrationRun($connectionId, $context);

        if ($runUuid === null) {
            throw MigrationException::runCouldNotBeCreated();
        }

        $this->updateMigrationRun($runUuid, $connection, $environmentInformation, $context, $dataSelectionIds);
        $this->updateUnprocessedMediaFiles($connectionId, $runUuid);

        $this->bus->dispatch(new MigrationProcessMessage($context, $runUuid));

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_STARTED, $runUuid, $context);
    }

    public function getRunStatus(Context $context): MigrationState
    {
        $run = $this->getActiveRun($context);

        if ($run === null || $run->getProgress() === null) {
            return new MigrationState(
                MigrationStep::IDLE,
                0,
                0
            );
        }

        $progress = $run->getProgress();

        return new MigrationState(
            $run->getStep(),
            $progress->getProgress(),
            $progress->getTotal(),
        );
    }

    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function updateConnectionCredentials(Context $context, string $connectionUuid, ?array $credentialFields): void
    {
        if ($this->isMigrationRunning($context)) {
            throw MigrationException::migrationProcessing();
        }

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionUuid, $credentialFields): void {
            $this->connectionRepo->update([
                [
                    'id' => $connectionUuid,
                    'credentialFields' => $credentialFields,
                ],
            ], $context);
        });
    }

    public function abortMigration(Context $context): void
    {
        $run = $this->getActiveRun($context);

        if ($run === null) {
            throw MigrationException::runNotFound();
        }

        $run->getStep()->assertOneOf(
            MigrationStep::FETCHING,
            MigrationStep::ERROR_RESOLUTION,
            MigrationStep::WRITING,
            MigrationStep::MEDIA_PROCESSING
        );

        $this->runTransitionService->transitionToRunStep(
            $run->getId(),
            MigrationStep::ABORTING
        );

        $this->bus->dispatch(new MigrationProcessMessage($context, $run->getId()));
        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_ABORTED, $run->getId(), $context);
    }

    public function startCleanupMappingChecksums(string $connectionId, Context $context): void
    {
        if ($this->isMigrationRunning($context)) {
            throw MigrationException::migrationProcessing();
        }

        $connection = $this->connectionRepo->search(
            new Criteria([$connectionId]),
            $context,
        )->getEntities()->first();

        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $affectedRows = $this->dbalConnection->executeStatement(
            'UPDATE swag_migration_general_setting SET `is_resetting_checksums` = 1 WHERE `is_resetting_checksums` = 0;'
        );

        if ($affectedRows === 0) {
            throw MigrationException::migrationProcessing('checksum reset');
        }

        $this->bus->dispatch(new ResetChecksumMessage(
            $connectionId,
            $context,
        ));
    }

    public function approveFinishingMigration(Context $context): void
    {
        $run = $this->getActiveRun($context);

        if ($run === null) {
            throw MigrationException::runNotFound();
        }

        $run->getStep()->assertOneOf(
            MigrationStep::WAITING_FOR_APPROVE
        );

        $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::FINISHED);

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_FINISHED, $run->getId(), $context);
    }

    public function startTruncateMigrationData(Context $context): void
    {
        if ($this->isMigrationRunning($context)) {
            throw MigrationException::migrationProcessing();
        }

        $affectedRows = $this->dbalConnection->executeStatement(
            'UPDATE swag_migration_general_setting SET selected_connection_id = NULL, `is_reset` = 1 WHERE `is_reset` = 0;'
        );

        if ($affectedRows === 0) {
            throw MigrationException::migrationProcessing('data truncation');
        }

        $this->bus->dispatch(new TruncateMigrationMessage());
    }

    public function assignThemeToSalesChannel(string $runUuid, Context $context): void
    {
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->getEntities()->first();

        if ($run === null) {
            return;
        }

        $connection = $run->getConnection();
        if ($connection === null) {
            return;
        }

        $connectionId = $connection->getId();
        $salesChannelIds = $this->getSalesChannels($connectionId, $context);
        $defaultThemeId = $this->getDefaultTheme($context);

        if ($defaultThemeId === null) {
            return;
        }

        foreach ($salesChannelIds as $salesChannelId) {
            try {
                $this->themeService->assignTheme($defaultThemeId, $salesChannelId, $context);
            } catch (\Throwable $exception) {
                $this->loggingService->log(
                    (new MigrationLogBuilder(
                        $runUuid,
                        $connection->getProfileName(),
                        $connection->getGatewayName(),
                    ))
                        ->withExceptionMessage($exception->getMessage())
                        ->withExceptionTrace($exception->getTrace())
                        ->withEntityName(ThemeDefinition::ENTITY_NAME)
                        ->withEntityId($defaultThemeId)
                        ->build(WriteThemeCompilingFailedLog::class)
                );
            }
        }
    }

    public function resumeAfterFixes(Context $context): void
    {
        $run = $this->getActiveRun($context);

        if ($run === null) {
            throw MigrationException::runNotFound();
        }

        $run->getStep()->assertOneOf(
            MigrationStep::ERROR_RESOLUTION
        );

        $logResult = $this->logGroupingService->getGroupedLogsByCodeAndEntity(
            $run->getId(),
            'error',
            1,
            1,
            'count',
            'DESC',
            null,
            'unresolved',
            null,
            null
        );

        if ($logResult['levelCounts']['error'] > 0) {
            throw MigrationException::unresolvedErrorsRemaining($logResult['levelCounts']['error']);
        }

        $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::WRITING);

        $this->bus->dispatch(new MigrationProcessMessage($context, $run->getId()));
    }

    /**
     * @param array<int, string> $dataSelectionIds
     */
    private function isPremappingValid(array $dataSelectionIds, SwagMigrationConnectionEntity $connection, Context $context): bool
    {
        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $premapping = $this->premappingService->generatePremapping($context, $migrationContext, $dataSelectionIds);

        foreach ($premapping as $item) {
            foreach ($item->getMapping() as $mapping) {
                if ($mapping->getDestinationUuid() === '') {
                    return false;
                }
            }
        }

        return true;
    }

    private function getActiveRun(Context $context): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('step', MigrationStep::ABORTED->value),
            new EqualsFilter('step', MigrationStep::FINISHED->value),
        ]));
        $criteria->setLimit(1);

        return $this->migrationRunRepo->search($criteria, $context)->getEntities()->first();
    }

    private function isMigrationRunning(Context $context): bool
    {
        return $this->getActiveRun($context) !== null;
    }

    private function isResettingChecksums(): bool
    {
        return (bool) $this->dbalConnection->fetchOne(
            'SELECT is_resetting_checksums FROM swag_migration_general_setting LIMIT 1'
        );
    }

    private function isTruncatingMigrationData(): bool
    {
        return (bool) $this->dbalConnection->fetchOne(
            'SELECT is_reset FROM swag_migration_general_setting LIMIT 1'
        );
    }

    private function fireTrackingInformation(string $eventName, string $runUuid, Context $context): void
    {
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->getEntities()->first();

        if ($run === null) {
            return;
        }

        $progress = $run->getProgress();
        $connection = $run->getConnection();
        $information = [];

        $time = $run->getUpdatedAt();
        if ($time === null) {
            $timestamp = (new \DateTime())->getTimestamp();
        } else {
            $timestamp = $time->getTimestamp();
        }

        if ($eventName === self::TRACKING_EVENT_MIGRATION_ABORTED) {
            if ($connection !== null) {
                $information['profileName'] = $connection->getProfileName();
                $information['gatewayName'] = $connection->getGatewayName();
            }

            $information['abortedAt'] = $timestamp;
            $this->trackingEventClient->fireTrackingEvent($eventName, $information);

            return;
        }

        if ($progress === null) {
            return;
        }

        $information['runProgress'] = $progress;

        if ($connection !== null) {
            $information['profileName'] = $connection->getProfileName();
            $information['gatewayName'] = $connection->getGatewayName();
        }

        if ($eventName === self::TRACKING_EVENT_MIGRATION_STARTED) {
            $information['startedAt'] = $timestamp;
        }

        if ($eventName === self::TRACKING_EVENT_MIGRATION_FINISHED) {
            $information['finishedAt'] = $timestamp;
        }

        $this->trackingEventClient->fireTrackingEvent($eventName, $information);
    }

    /**
     * @param array<int, string> $dataSelectionIds
     */
    private function updateMigrationRun(
        string $runUuid,
        SwagMigrationConnectionEntity $connection,
        EnvironmentInformation $environmentInformation,
        Context $context,
        array $dataSelectionIds,
    ): void {
        $credentials = $connection->getCredentialFields();

        if (empty($credentials)) {
            $credentials = [];
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $dataSelectionCollection = $this->getDataSelectionCollection($migrationContext, $environmentInformation, $dataSelectionIds);
        $runProgress = $this->calculateRunProgress($environmentInformation, $dataSelectionCollection);

        $this->updateRunWithProgress(
            $runUuid,
            $credentials,
            $environmentInformation,
            $runProgress,
            $context
        );
    }

    private function calculateRunProgress(
        EnvironmentInformation $environmentInformation,
        DataSelectionCollection $dataSelectionCollection,
    ): MigrationProgress {
        $dataSetCollection = $this->calculateToBeFetchedTotals($environmentInformation, $dataSelectionCollection);
        $overallTotal = 0;

        $dataSetCollection->map(function (ProgressDataSet $progressDataSet) use (&$overallTotal): void {
            $overallTotal += $progressDataSet->getTotal();
        });

        $firstDataSet = $dataSetCollection->first();
        if ($firstDataSet === null) {
            throw MigrationException::noDataToMigrate();
        }

        return new MigrationProgress(
            0,
            $overallTotal,
            $dataSetCollection,
            $firstDataSet->getEntityName(),
            0
        );
    }

    private function getCurrentConnection(Context $context): ?SwagMigrationConnectionEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $setting = $this->generalSettingRepo->search($criteria, $context)->getEntities()->first();

        if ($setting === null) {
            return null;
        }

        $connectionId = $setting->getSelectedConnectionId();

        if ($connectionId === null) {
            return null;
        }

        return $this->connectionRepo->search(new Criteria([$connectionId]), $context)->getEntities()->first();
    }

    private function createPlainMigrationRun(string $connectionId, Context $context): ?string
    {
        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'connectionId' => $connectionId,
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $context
        );

        $event = $writtenEvent->getEventByEntityName(SwagMigrationRunDefinition::ENTITY_NAME);

        if ($event === null) {
            return null;
        }

        $ids = $event->getIds();

        return \array_pop($ids);
    }

    private function getEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        return $this->migrationDataFetcher->getEnvironmentInformation($migrationContext, $context);
    }

    /**
     * @param array<string, int|string> $credentials
     */
    private function updateRunWithProgress(
        string $runId,
        array $credentials,
        EnvironmentInformation $environmentInformation,
        MigrationProgress $runProgress,
        Context $context,
    ): void {
        $this->migrationRunRepo->update(
            [
                [
                    'id' => $runId,
                    'environmentInformation' => $environmentInformation->jsonSerialize(),
                    'credentialFields' => $credentials,
                    'progress' => $runProgress->jsonSerialize(),
                ],
            ],
            $context
        );
    }

    /**
     * @param array<int, string> $dataSelectionIds
     */
    private function getDataSelectionCollection(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation, array $dataSelectionIds): DataSelectionCollection
    {
        return $this->dataSelectionRegistry->getDataSelectionsByIds($migrationContext, $environmentInformation, $dataSelectionIds);
    }

    private function calculateToBeFetchedTotals(EnvironmentInformation $environmentInformation, DataSelectionCollection $dataSelectionCollection): ProgressDataSetCollection
    {
        $environmentInformationTotals = $environmentInformation->getTotals();
        $totals = new ProgressDataSetCollection();
        foreach ($dataSelectionCollection as $dataSelection) {
            foreach (\array_keys($dataSelection->getEntityNames()) as $entityName) {
                if (isset($environmentInformationTotals[$entityName]) && $totals->get($entityName) === null) {
                    $totals->set($entityName, new ProgressDataSet($entityName, $environmentInformationTotals[$entityName]->getTotal()));
                } elseif ($totals->get($entityName) === null) {
                    $totals->set($entityName, new ProgressDataSet($entityName, 1));
                }
            }
        }

        return $totals;
    }

    /**
     * @return array<string>
     */
    private function getSalesChannels(string $connectionId, Context $context): array
    {
        $salesChannelUuids = $this->mappingService->getUuidsByEntity(
            $connectionId,
            SalesChannelDefinition::ENTITY_NAME,
            $context
        );

        if (empty($salesChannelUuids)) {
            return [];
        }

        return $this->salesChannelRepository->search(new Criteria($salesChannelUuids), $context)->getIds();
    }

    private function getDefaultTheme(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'Storefront'));

        $ids = $this->themeRepository->search($criteria, $context)->getIds();

        if (empty($ids)) {
            return null;
        }

        return \reset($ids);
    }

    private function updateUnprocessedMediaFiles(string $connectionId, string $runUuid): void
    {
        $sql = <<<SQL
UPDATE swag_migration_media_file AS mediafile
INNER JOIN swag_migration_run AS run ON run.id = mediafile.run_id
SET mediafile.run_id = UNHEX(?)
WHERE HEX(run.connection_id) = ?
AND mediafile.processed = 0
AND mediafile.written = 1;
SQL;
        $this->dbalConnection->executeStatement(
            $sql,
            [$runUuid, $connectionId],
            [ParameterType::STRING, ParameterType::STRING]
        );
    }
}
