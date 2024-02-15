<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Logging\Log\ThemeCompilingErrorRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\CleanupMigrationMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class RunService implements RunServiceInterface
{
    private const TRACKING_EVENT_MIGRATION_STARTED = 'Migration started';
    private const TRACKING_EVENT_MIGRATION_FINISHED = 'Migration finished';
    private const TRACKING_EVENT_MIGRATION_ABORTED = 'Migration aborted';

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationConnectionCollection> $connectionRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepository
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<ThemeCollection> $themeRepository
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
        private readonly EntityRepository $connectionRepo,
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly SwagMigrationAccessTokenService $accessTokenService,
        private readonly DataSelectionRegistryInterface $dataSelectionRegistry,
        private readonly EntityRepository $migrationDataRepository,
        private readonly EntityRepository $mediaFileRepository,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $themeRepository,
        private readonly EntityRepository $generalSettingRepo,
        private readonly EntityIndexerRegistry $indexer,
        private readonly ThemeService $themeService,
        private readonly MappingServiceInterface $mappingService,
        private readonly TagAwareAdapterInterface $cache,
        private readonly EntityDefinition $migrationDataDefinition,
        private readonly Connection $dbalConnection,
        private readonly LoggingServiceInterface $loggingService,
        private readonly TrackingEventClient $trackingEventClient,
        private readonly MessageBusInterface $bus,
        private readonly MigrationContextFactoryInterface $migrationContextFactory
    ) {
    }

    public function startMigrationRun(array $dataSelectionIds, Context $context): void
    {
        if ($this->isMigrationRunning($context)) {
            throw new \Exception('Migration is already running');
        }

        $connection = $this->getCurrentConnection($context);

        if ($connection === null) {
            throw new \Exception('Connection is not selected');
        }

        $connectionId = $connection->getId();
        // Maybe remove this or put this in the MQ
        $this->cleanupUnwrittenRunData($connectionId, $context);

        $runUuid = $this->createPlainMigrationRun($connectionId, $context);

        if ($runUuid === null) {
            throw new \Exception('Could not create migration run');
        }

        $this->updateMigrationRun($runUuid, $connection, $context, $dataSelectionIds);
        $this->updateUnprocessedMediaFiles($connectionId, $runUuid);

        // Todo: Checking, if premapping is filled
        $this->bus->dispatch(new MigrationProcessMessage($context, $runUuid));

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_STARTED, $runUuid, $context);
    }

    public function getRunStatus(Context $context): MigrationProgress
    {
        $run = $this->getCurrentRun($context);

        if ($run === null || $run->getProgress() === null) {
            return new MigrationProgress(MigrationProgress::STATUS_IDLE, 0, 0, [], '', 0);
        }

        return $run->getProgress();
    }

    /**
     * @param array<int, string>|null $credentialFields
     */
    public function updateConnectionCredentials(Context $context, string $connectionUuid, ?array $credentialFields): void
    {
        $isMigrationRunning = $this->isMigrationRunningWithGivenConnection($context, $connectionUuid);

        if ($isMigrationRunning) {
            throw new MigrationIsRunningException();
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
        $run = $this->getCurrentRun($context);

        if ($run === null) {
            throw new \Exception('No running migration found');
        }

        $runId = $run->getId();
        $progress = $run->getProgress();
        $progress->setStep(MigrationProgress::STATUS_ABORTING);

        $this->migrationRunRepo->update(
            [
                [
                    'id' => $runId,
                    'status' => SwagMigrationRunEntity::STATUS_ABORTED,
                    'progress' => $progress->jsonSerialize(),
                ],
            ],
            $context
        );

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_ABORTED, $runId, $context);
    }

    public function cleanupMappingChecksums(string $connectionUuid, Context $context, bool $resetAll = true): void
    {
        $sql = <<<SQL
UPDATE swag_migration_mapping
SET checksum = null
WHERE HEX(connection_id) = ?
AND checksum IS NOT NULL;
SQL;
        if ($resetAll === false) {
            $sql = <<<SQL
UPDATE swag_migration_mapping AS m
INNER JOIN swag_migration_data d ON d.mapping_uuid = m.id
SET m.checksum = null
WHERE HEX(m.connection_id) = ?
AND d.written = 0
AND m.checksum IS NOT NULL;
SQL;
        }

        $this->dbalConnection->executeStatement(
            $sql,
            [$connectionUuid],
            [\PDO::PARAM_STR]
        );
    }

    public function finishMigration(Context $context): void
    {
        $run = $this->getCurrentRun($context);

        if ($run === null) {
            throw new \Exception('No running migration found');
        }

        $progress = $run->getProgress();

        if ($progress->getStep() !== MigrationProgress::STATUS_WAITING_FOR_APPROVE) {
            throw new \Exception('No migration to finish found');
        }

        $progress->setStep(MigrationProgress::STATUS_FINISHED);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'status' => SwagMigrationRunEntity::STATUS_FINISHED,
                'progress' => $progress->jsonSerialize(),
            ],
        ], $context);

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_FINISHED, $run->getId(), $context);
    }

    public function cleanupMigrationData(): void
    {
        $result = $this->dbalConnection->fetchOne('SELECT 1 FROM swag_migration_run WHERE `status` = :status', ['status' => SwagMigrationRunEntity::STATUS_RUNNING]);

        if ($result !== false) {
            throw new MigrationIsRunningException();
        }

        $this->dbalConnection->executeStatement('UPDATE swag_migration_general_setting SET selected_connection_id = NULL, `is_reset` = 1;');
        $this->bus->dispatch(new CleanupMigrationMessage());
    }

    public function assignThemeToSalesChannel(string $runUuid, Context $context): void
    {
        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            return;
        }

        $connection = $run->getConnection();
        if ($connection === null) {
            return;
        }

        $connectionId = $connection->getId();
        $salesChannels = $this->getSalesChannels($connectionId, $context);
        $defaultTheme = $this->getDefaultTheme($context);

        if ($defaultTheme === null) {
            return;
        }

        foreach ($salesChannels as $salesChannel) {
            try {
                $this->themeService->assignTheme($defaultTheme, $salesChannel, $context);
            } catch (\Throwable $exception) {
                $this->loggingService->addLogEntry(new ThemeCompilingErrorRunLog(
                    $runUuid,
                    $defaultTheme
                ));
            }
        }

        $this->loggingService->saveLogging($context);
    }

    private function getCurrentRun(Context $context): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('progress.step', MigrationProgress::STATUS_ABORTED),
            new EqualsFilter('progress.step', MigrationProgress::STATUS_FINISHED),
        ]));
        $criteria->setLimit(1);

        return $this->migrationRunRepo->search($criteria, $context)->getEntities()->first();
    }

    private function fireTrackingInformation(string $eventName, string $runUuid, Context $context): void
    {
        /** @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();
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

        if ($information === []) {
            return;
        }

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

    private function cleanupMigration(string $runUuid, bool $removeOnlyWrittenData = false): void
    {
        $this->removeMigrationData($runUuid, $removeOnlyWrittenData);

        $this->cache->clear();
        $this->indexer->index(true);
    }

    private function isMigrationRunningWithGivenConnection(Context $context, string $connectionUuid): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('connectionId', $connectionUuid),
            new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING)
        );

        $runCount = $this->migrationRunRepo->search($criteria, $context)->getEntities()->count();

        return $runCount > 0;
    }

    /**
     * @param array<int, string> $dataSelectionIds
     */
    private function updateMigrationRun(
        string $runUuid,
        SwagMigrationConnectionEntity $connection,
        Context $context,
        array $dataSelectionIds
    ): void {
        $credentials = $connection->getCredentialFields();

        if (empty($credentials)) {
            $credentials = [];
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $environmentInformation = $this->getEnvironmentInformation($migrationContext, $context);
        $dataSelectionCollection = $this->getDataSelectionCollection($migrationContext, $environmentInformation, $dataSelectionIds);
        $runProgress = $this->calculateRunProgress($environmentInformation, $dataSelectionCollection);

        $this->updateRunWithProgress($runUuid, $credentials, $environmentInformation, $runProgress, $context);
    }

    /**
     * @return array<RunProgress>
     */
    private function calculateRunProgress(
        EnvironmentInformation $environmentInformation,
        DataSelectionCollection $dataSelectionCollection
    ): MigrationProgress {
        $totals = $this->calculateToBeFetchedTotals($environmentInformation, $dataSelectionCollection);
        $overallTotal = \array_sum($totals);
        $dataSets = \array_keys($totals);

        return new MigrationProgress(
            MigrationProgress::STATUS_FETCHING,
            0,
            $overallTotal,
            $totals,
            \current($dataSets),
            0
        );
    }

    private function isMigrationRunning(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));
        $total = $this->migrationRunRepo->searchIds($criteria, $context)->getTotal();

        return $total > 0;
    }

    private function getCurrentConnection(Context $context): ?SwagMigrationConnectionEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $setting = $this->generalSettingRepo->search($criteria, $context)->getEntities()->first();

        $connectionId = $setting->getSelectedConnectionId();

        if ($connectionId === null) {
            return null;
        }

        return $this->connectionRepo->search(new Criteria([$connectionId]), $context)->first();
    }

    private function createPlainMigrationRun(string $connectionId, Context $context): ?string
    {
        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'connectionId' => $connectionId,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
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
     * @param array<int, string> $credentials
     */
    private function updateRunWithProgress(
        string $runId,
        array $credentials,
        EnvironmentInformation $environmentInformation,
        MigrationProgress $runProgress,
        Context $context
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

    /**
     * @return array<string, int>
     */
    private function calculateToBeFetchedTotals(EnvironmentInformation $environmentInformation, DataSelectionCollection $dataSelectionCollection): array
    {
        $environmentInformationTotals = $environmentInformation->getTotals();
        $totals = [];
        foreach ($dataSelectionCollection as $dataSelection) {
            foreach (\array_keys($dataSelection->getEntityNames()) as $entityName) {
                if (isset($environmentInformationTotals[$entityName]) && !isset($totals[$entityName])) {
                    $totals[$entityName] = $environmentInformationTotals[$entityName]->getTotal();
                } elseif (!isset($totals[$entityName])) {
                    $totals[$entityName] = 1;
                }
            }
        }

        return $totals;
    }

    private function removeMigrationData(string $runUuid, bool $onlyWritten = false): void
    {
        $qb = new QueryBuilder($this->dbalConnection);
        $qb->delete($this->migrationDataDefinition->getEntityName())
            ->andWhere('HEX(run_id) = :runId');

        if ($onlyWritten === true) {
            $qb->andWhere('written = 1');
        }

        $qb->setParameter('runId', $runUuid)
            ->executeStatement();
    }

    /**
     * @return array<string>
     */
    private function getSalesChannels(string $connectionId, Context $context): array
    {
        /** @var array<int, string> $salesChannelUuids */
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

    private function cleanupUnwrittenRunData(string $connectionId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('connectionId', $connectionId),
            new EqualsAnyFilter('status', [SwagMigrationRunEntity::STATUS_FINISHED, SwagMigrationRunEntity::STATUS_ABORTED])
        );
        $criteria->addSorting(new FieldSorting('createdAt', 'DESC'));
        $criteria->setLimit(1);

        $idSearchResult = $this->migrationRunRepo->searchIds($criteria, $context);

        if ($idSearchResult->firstId() !== null) {
            $lastRunUuid = $idSearchResult->firstId();

            $qb = new QueryBuilder($this->dbalConnection);
            $qb->delete($this->migrationDataDefinition->getEntityName())
                ->andWhere('HEX(run_id) = :runId')
                ->setParameter('runId', $lastRunUuid)
                ->executeStatement();
        }
    }

    private function getMigrationDataCount(string $runUuid, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $result = $this->migrationDataRepository->aggregate($criteria, $context);

        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
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
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }
}
