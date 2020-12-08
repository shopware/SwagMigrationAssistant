<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Logging\Log\ThemeCompilingErrorRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\CleanupMigrationMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class RunService implements RunServiceInterface
{
    private const TRACKING_EVENT_MIGRATION_STARTED = 'Migration started';
    private const TRACKING_EVENT_MIGRATION_FINISHED = 'Migration finished';
    private const TRACKING_EVENT_MIGRATION_ABORTED = 'Migration aborted';

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $accessTokenService;

    /**
     * @var DataSelectionRegistryInterface
     */
    private $dataSelectionRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepository;

    /**
     * @var EntityIndexerRegistry
     */
    private $indexer;

    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeRepository;

    /**
     * @var EntityDefinition
     */
    private $migrationDataDefinition;

    /**
     * @var Connection
     */
    private $dbalConnection;

    /**
     * @var ThemeService
     */
    private $themeService;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var StoreService
     */
    private $storeService;

    /**
     * @var MessageBusInterface
     */
    private $bus;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $connectionRepo,
        MigrationDataFetcherInterface $migrationDataFetcher,
        SwagMigrationAccessTokenService $accessTokenService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationDataRepository,
        EntityRepositoryInterface $mediaFileRepository,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $themeRepository,
        EntityIndexerRegistry $indexer,
        ThemeService $themeService,
        MappingServiceInterface $mappingService,
        TagAwareAdapterInterface $cache,
        EntityDefinition $migrationDataDefinition,
        Connection $dbalConnection,
        LoggingServiceInterface $loggingService,
        StoreService $storeService,
        MessageBusInterface $bus
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->connectionRepo = $connectionRepo;
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->accessTokenService = $accessTokenService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->mediaFileRepository = $mediaFileRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->themeRepository = $themeRepository;
        $this->indexer = $indexer;
        $this->themeService = $themeService;
        $this->mappingService = $mappingService;
        $this->cache = $cache;
        $this->migrationDataDefinition = $migrationDataDefinition;
        $this->dbalConnection = $dbalConnection;
        $this->loggingService = $loggingService;
        $this->storeService = $storeService;
        $this->bus = $bus;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        return $this->accessTokenService->updateRunAccessToken($runUuid, $context);
    }

    public function createMigrationRun(MigrationContextInterface $migrationContext, array $dataSelectionIds, Context $context): ?ProgressState
    {
        if ($this->isMigrationRunning($context)) {
            return null;
        }
        $this->cleanupUnwrittenRunData($migrationContext, $context);

        $runUuid = $this->createPlainMigrationRun($migrationContext, $context);

        if ($runUuid === null) {
            return null;
        }

        $accessToken = $this->accessTokenService->updateRunAccessToken($runUuid, $context);

        $environmentInformation = $this->getEnvironmentInformation($migrationContext, $context);
        $dataSelectionCollection = $this->getDataSelectionCollection($migrationContext, $environmentInformation, $dataSelectionIds);
        $runProgress = $this->calculateRunProgress($environmentInformation, $dataSelectionCollection);

        $this->updateMigrationRun($runUuid, $migrationContext, $environmentInformation, $runProgress, $context);
        $this->updateUnprocessedMediaFiles($migrationContext, $runUuid);

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_STARTED, $runUuid, $context);

        return new ProgressState(false, true, $runUuid, $accessToken, -1, null, 0, 0, $runProgress);
    }

    public function calculateWriteProgress(SwagMigrationRunEntity $run, Context $context): array
    {
        $unsortedTotals = $this->calculateFetchedTotals($run->getId());
        $writeProgress = $run->getProgress();

        if ($writeProgress === null) {
            return [];
        }

        foreach ($writeProgress as $runKey => $runProgress) {
            $groupCount = 0;

            foreach ($runProgress['entities'] as $entityKey => $entityProgress) {
                $entityName = $entityProgress['entityName'];
                $writeProgress[$runKey]['entities'][$entityKey]['currentCount'] = 0;

                if (!isset($unsortedTotals[$entityName])) {
                    $writeProgress[$runKey]['entities'][$entityKey]['total'] = 0;

                    continue;
                }

                $writeProgress[$runKey]['entities'][$entityKey]['total'] = $unsortedTotals[$entityName];
                $groupCount += $unsortedTotals[$entityName];
            }

            $writeProgress[$runKey]['currentCount'] = 0;
            $writeProgress[$runKey]['total'] = $groupCount;
        }

        return $writeProgress;
    }

    public function calculateMediaFilesProgress(SwagMigrationRunEntity $run, Context $context): array
    {
        $currentProgress = $run->getProgress();
        if ($currentProgress === null) {
            return [];
        }

        $mediaFileTotal = $this->getMediaFileCounts($run->getId(), $context, false);
        $mediaFileCount = $this->getMediaFileCounts($run->getId(), $context, true);

        foreach ($currentProgress as &$runProgress) {
            if ($runProgress['id'] === 'processMediaFiles') {
                foreach ($runProgress['entities'] as &$entity) {
                    if ($entity['entityName'] === 'media') {
                        $entity['currentCount'] = $mediaFileCount;
                        $entity['total'] = $mediaFileTotal;
                    }
                }
                $runProgress['currentCount'] = $mediaFileCount;
                $runProgress['total'] = $mediaFileTotal;

                break;
            }
        }

        return $currentProgress;
    }

    /**
     * @return int[]
     */
    public function calculateCurrentTotals(string $runId, bool $isWritten, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        if ($isWritten) {
            $criteria->addFilter(new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new MultiFilter(
                        MultiFilter::CONNECTION_AND,
                        [
                            new EqualsFilter('written', true),
                            new EqualsFilter('writeFailure', false),
                        ]
                    ),

                    new MultiFilter(
                        MultiFilter::CONNECTION_AND,
                        [
                            new EqualsFilter('written', false),
                            new EqualsFilter('writeFailure', true),
                        ]
                    ),
                ]
            ));
        }
        $criteria->addAggregation(new TermsAggregation('entityCount', 'entity'));
        $result = $this->migrationDataRepository->aggregate($criteria, $context);
        /** @var TermsResult $termsResult */
        $termsResult = $result->get('entityCount');
        $counts = $termsResult->getBuckets();

        if (empty($counts)) {
            return [];
        }

        $mappedCounts = [];
        foreach ($counts as $bucket) {
            $key = $bucket->getKey();

            if ($key === null) {
                continue;
            }

            $mappedCounts[$key] = $bucket->getCount();
        }

        return $mappedCounts;
    }

    /**
     * @throws MigrationIsRunningException
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

    public function abortMigration(string $runUuid, Context $context): void
    {
        $this->accessTokenService->invalidateRunAccessToken($runUuid, $context);
        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_ABORTED, $runUuid, $context);
        $dataCount = $this->getMigrationDataCount($runUuid, $context);
        if ($dataCount > 0) {
            /** @var SwagMigrationRunEntity $run */
            $run = $this->migrationRunRepo->search(new Criteria([$runUuid]), $context)->first();
            $connection = $run->getConnection();

            if ($connection === null) {
                return;
            }

            $this->cleanupMappingChecksums($connection->getId(), $context, false);
        }
        $this->cleanupMigration($runUuid);
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

    public function finishMigration(string $runUuid, Context $context): void
    {
        $this->migrationRunRepo->update([
            [
                'id' => $runUuid,
                'status' => 'finished',
            ],
        ], $context);

        $this->fireTrackingInformation(self::TRACKING_EVENT_MIGRATION_FINISHED, $runUuid, $context);

        $this->cleanupMigration($runUuid, true);
    }

    public function cleanupMigrationData(): void
    {
        $result = $this->dbalConnection->executeQuery('SELECT * FROM swag_migration_run WHERE `status` = :status', ['status' => SwagMigrationRunEntity::STATUS_RUNNING])->fetch();

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
            $this->storeService->fireTrackingEvent($eventName, $information);

            return;
        }

        if ($progress === null) {
            return;
        }

        foreach ($progress as $entityGroup) {
            if ($entityGroup['total'] === 0) {
                continue;
            }

            foreach ($entityGroup['entities'] as $entity) {
                if ($entity['total'] === 0) {
                    continue;
                }

                $entityName = $entity['entityName'];
                $information['totals'][$entityName] = $entity['total'];
            }
        }

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

        $this->storeService->fireTrackingEvent($eventName, $information);
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

    private function getMediaFileCounts(string $runId, Context $context, bool $onlyProcessed = true): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('written', true));
        if ($onlyProcessed) {
            $criteria->addFilter(new EqualsFilter('processed', true));
        }
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);
        $result = $this->mediaFileRepository->aggregate($criteria, $context);
        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
    }

    private function calculateFetchedTotals(string $runId): array
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder();
        $query = $queryBuilder
            ->select('entity, COUNT(id) AS total')
            ->from('swag_migration_data')
            ->where('HEX(run_id) = :runId')
            ->andWhere('convert_failure = 0 AND converted IS NOT NULL')
            ->groupBy('entity')
            ->setParameter('runId', $runId)
            ->execute();

        if (!($query instanceof ResultStatement)) {
            return [];
        }

        $results = $query->fetchAll(FetchMode::ASSOCIATIVE);

        $mappedCounts = [];
        foreach ($results as $result) {
            $mappedCounts[$result['entity']] = (int) $result['total'];
        }

        return $mappedCounts;
    }

    private function updateMigrationRun(
        string $runUuid,
        MigrationContextInterface $migrationContext,
        EnvironmentInformation $environmentInformation,
        array $runProgress,
        Context $context
    ): void {
        $connection = $migrationContext->getConnection();

        $credentials = [];
        if ($connection !== null) {
            $credentials = $connection->getCredentialFields();
        }

        if (empty($credentials)) {
            $credentials = [];
        }

        $this->updateRunWithProgress($runUuid, $credentials, $environmentInformation, $runProgress, $context);
    }

    /**
     * @return RunProgress[]
     */
    private function calculateRunProgress(
        EnvironmentInformation $environmentInformation,
        DataSelectionCollection $dataSelectionCollection
    ): array {
        $totals = $this->calculateToBeFetchedTotals($environmentInformation, $dataSelectionCollection);
        $runProgressArray = [];
        $processMediaFiles = false;
        $entityNamesInUse = [];

        foreach ($dataSelectionCollection as $dataSelection) {
            $entities = [];
            $sumTotal = 0;
            foreach (\array_keys($dataSelection->getEntityNames()) as $entityName) {
                if (isset($entityNamesInUse[$entityName])) {
                    continue;
                }

                $total = 0;
                if (isset($totals[$entityName])) {
                    $total = $totals[$entityName];
                }

                $entityProgress = new EntityProgress();
                $entityProgress->setEntityName((string) $entityName);
                $entityProgress->setCurrentCount(0);
                $entityProgress->setTotal($total);

                $entities[] = $entityProgress;
                $sumTotal += $total;
                $entityNamesInUse[$entityName] = $entityName;
            }

            if (empty($entities)) {
                continue;
            }

            $runProgress = new RunProgress();
            $runProgress->setId($dataSelection->getId());
            $runProgress->setEntities($entities);
            $runProgress->setCurrentCount(0);
            $runProgress->setTotal($sumTotal);
            $runProgress->setSnippet($dataSelection->getSnippet());
            $runProgress->setProcessMediaFiles($dataSelection->getProcessMediaFiles());

            if ($dataSelection->getProcessMediaFiles()) {
                $processMediaFiles = true;
            }

            $runProgressArray[] = $runProgress;
        }

        if ($processMediaFiles) {
            $runProgressArray[] = $this->calculateProcessMediaFileProgress();
        }

        return $runProgressArray;
    }

    private function calculateProcessMediaFileProgress(): RunProgress
    {
        $entityProgress = new EntityProgress();
        $entityProgress->setEntityName(DefaultEntities::MEDIA);
        $entityProgress->setCurrentCount(0);
        $entityProgress->setTotal(0);

        $runProgress = new RunProgress();
        $runProgress->setId('processMediaFiles');
        $runProgress->setEntities([$entityProgress]);
        $runProgress->setCurrentCount(0);
        $runProgress->setTotal(0);
        $runProgress->setSnippet('swag-migration.index.selectDataCard.dataSelection.media');

        return $runProgress;
    }

    private function isMigrationRunning(Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));
        $total = $this->migrationRunRepo->searchIds($criteria, $context)->getTotal();

        return $total > 0;
    }

    private function createPlainMigrationRun(MigrationContextInterface $migrationContext, Context $context): ?string
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return null;
        }

        $writtenEvent = $this->migrationRunRepo->create(
            [
                [
                    'connectionId' => $connection->getId(),
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

    private function updateRunWithProgress(
        string $runId,
        array $credentials,
        EnvironmentInformation $environmentInformation,
        array $runProgress,
        Context $context
    ): void {
        $this->migrationRunRepo->update(
            [
                [
                    'id' => $runId,
                    'environmentInformation' => $environmentInformation->jsonSerialize(),
                    'credentialFields' => $credentials,
                    'progress' => $runProgress,
                ],
            ],
            $context
        );
    }

    private function getDataSelectionCollection(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation, array $dataSelectionIds): DataSelectionCollection
    {
        return $this->dataSelectionRegistry->getDataSelectionsByIds($migrationContext, $environmentInformation, $dataSelectionIds);
    }

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
            ->execute();
    }

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

    private function cleanupUnwrittenRunData(MigrationContextInterface $migrationContext, Context $context): void
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('connectionId', $connection->getId()),
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
                ->execute();
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

    private function updateUnprocessedMediaFiles(MigrationContextInterface $migrationContext, string $runUuid): void
    {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $connectionId = $connection->getId();

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
