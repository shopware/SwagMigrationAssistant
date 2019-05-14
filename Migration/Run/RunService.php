<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Indexing\IndexerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class RunService implements RunServiceInterface
{
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
     * @var IndexerInterface
     */
    private $indexer;

    /** @var TagAwareAdapter */
    private $cache;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $connectionRepo,
        MigrationDataFetcherInterface $migrationDataFetcher,
        SwagMigrationAccessTokenService $accessTokenService,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        EntityRepositoryInterface $migrationDataRepository,
        EntityRepositoryInterface $mediaFileRepository,
        EntityRepositoryInterface $currencyRepository,
        IndexerInterface $indexer,
        TagAwareAdapter $cache
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->connectionRepo = $connectionRepo;
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->accessTokenService = $accessTokenService;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->mediaFileRepository = $mediaFileRepository;
        $this->currencyRepository = $currencyRepository;
        $this->indexer = $indexer;
        $this->cache = $cache;
    }

    public function takeoverMigration(string $runUuid, Context $context): string
    {
        return $this->accessTokenService->updateRunAccessToken($runUuid, $context);
    }

    public function abortMigration(string $runUuid, Context $context): void
    {
        $this->accessTokenService->invalidateRunAccessToken($runUuid, $context);

        $this->removeDuplicateCurrencies($context);
        $this->cache->clear();
    }

    public function createMigrationRun(string $connectionId, array $dataSelectionIds, Context $context): ?ProgressState
    {
        if ($this->isMigrationRunning($context)) {
            return null;
        }

        $runUuid = $this->createPlainMigrationRun($connectionId, $context);
        $accessToken = $this->accessTokenService->updateRunAccessToken($runUuid, $context);
        $connection = $this->getConnection($connectionId, $context);

        $environmentInformation = $this->getEnvironmentInformation($connection, $context);
        $dataSelectionCollection = $this->getDataSelectionCollection($connection, $environmentInformation, $dataSelectionIds);
        $runProgress = $this->calculateRunProgress($environmentInformation, $dataSelectionCollection);

        $this->updateMigrationRun($runUuid, $connection, $environmentInformation, $runProgress, $context);

        return new ProgressState(false, true, $runUuid, $accessToken, -1, null, 0, 0, $runProgress);
    }

    public function calculateWriteProgress(SwagMigrationRunEntity $run, Context $context): array
    {
        $unsortedTotals = $this->calculateFetchedTotals($run->getId(), $context);
        $writeProgress = $run->getProgress();

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

    public function calculateCurrentTotals(string $runId, bool $isWritten, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        if ($isWritten) {
            $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));
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
        $criteria->addAggregation(new ValueCountAggregation('entity', 'entityCount'));
        /** @var AggregatorResult $result */
        $result = $this->migrationDataRepository->aggregate($criteria, $context);
        $counts = $result->getAggregations()->get('entityCount')->getResult();

        if (!isset($counts[0]['values'])) {
            return [];
        }

        $mappedCounts = [];
        foreach ($counts[0]['values'] as $item) {
            $mappedCounts[$item['key']] = (int) $item['count'];
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

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionUuid, $credentialFields) {
            $this->connectionRepo->update([
                [
                    'id' => $connectionUuid,
                    'credentialFields' => $credentialFields,
                ],
            ], $context);
        });
    }

    public function finishMigration(Context $context, string $runUuid): void
    {
        $this->migrationRunRepo->update([
            [
                'id' => $runUuid,
                'status' => 'finished',
            ],
        ], $context);

        $this->removeDuplicateCurrencies($context);
        $this->removeWrittenMigrationData($context, $runUuid);

        $this->cache->clear();

        $this->indexer->index(new \DateTime());
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
        $criteria->addAggregation(new CountAggregation('id', 'count'));
        $criteria->setLimit(1);
        /** @var AggregatorResult $result */
        $result = $this->mediaFileRepository->aggregate($criteria, $context);

        return (int) $result->getAggregations()->get('count')->getResult()[0]['count'];
    }

    private function calculateFetchedTotals(string $runId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('convertFailure', false));
        $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));
        $criteria->addAggregation(new ValueCountAggregation('entity', 'entityCount'));
        /** @var AggregatorResult $result */
        $result = $this->migrationDataRepository->aggregate($criteria, $context);
        $counts = $result->getAggregations()->get('entityCount')->getResult();

        if (!isset($counts[0]['values'])) {
            return [];
        }

        $mappedCounts = [];
        foreach ($counts[0]['values'] as $item) {
            $mappedCounts[$item['key']] = (int) $item['count'];
        }

        return $mappedCounts;
    }

    private function updateMigrationRun(
        string $runUuid,
        SwagMigrationConnectionEntity $connection,
        EnvironmentInformation $environmentInformation,
        array $runProgress,
        Context $context
    ): void {
        $credentials = $connection->getCredentialFields();

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

        /** @var DataSelectionStruct $dataSelection */
        foreach ($dataSelectionCollection as $dataSelection) {
            $entities = [];
            $sumTotal = 0;
            foreach ($dataSelection->getEntityNames() as $entityName) {
                $total = 0;
                if (isset($totals[$entityName])) {
                    $total = $totals[$entityName];
                }

                $entityProgress = new EntityProgress();
                $entityProgress->setEntityName($entityName);
                $entityProgress->setCurrentCount(0);
                $entityProgress->setTotal($total);

                $entities[] = $entityProgress;
                $sumTotal += $total;
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

    private function createPlainMigrationRun(string $connectionId, Context $context): string
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

        $ids = $writtenEvent->getEventByDefinition(SwagMigrationRunDefinition::class)->getIds();

        return array_pop($ids);
    }

    private function getEnvironmentInformation(SwagMigrationConnectionEntity $connection, Context $context): EnvironmentInformation
    {
        $migrationContext = new MigrationContext(
            $connection
        );

        return $this->migrationDataFetcher->getEnvironmentInformation($migrationContext);
    }

    private function getConnection(string $connectionId, Context $context): SwagMigrationConnectionEntity
    {
        $criteria = new Criteria([$connectionId]);

        return $this->connectionRepo->search($criteria, $context)->first();
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

    private function getDataSelectionCollection(SwagMigrationConnectionEntity $connection, EnvironmentInformation $environmentInformation, array $dataSelectionIds): DataSelectionCollection
    {
        $migrationContext = new MigrationContext(
            $connection
        );

        return $this->dataSelectionRegistry->getDataSelectionsByIds($migrationContext, $environmentInformation, $dataSelectionIds);
    }

    private function calculateToBeFetchedTotals(EnvironmentInformation $environmentInformation, DataSelectionCollection $dataSelectionCollection): array
    {
        $environmentInformationTotals = $environmentInformation->getTotals();
        $totals = [];
        /** @var DataSelectionStruct $dataSelection */
        foreach ($dataSelectionCollection as $dataSelection) {
            foreach ($dataSelection->getEntityNames() as $entityName) {
                if (isset($environmentInformationTotals[$entityName]) && !isset($totals[$entityName])) {
                    $totals[$entityName] = $environmentInformationTotals[$entityName];
                } elseif (!isset($totals[$entityName])) {
                    $totals[$entityName] = 1;
                }
            }
        }

        return $totals;
    }

    private function removeWrittenMigrationData(Context $context, string $runUuid): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('runId', $runUuid),
            new EqualsFilter('written', true)
        );
        $result = $this->migrationDataRepository->searchIds($criteria, $context);

        if ($result->getTotal() > 0) {
            $this->migrationDataRepository->delete(array_values($result->getData()), $context);
        }
    }

    private function removeDuplicateCurrencies(Context $context): void
    {
        $currenciesToDelete = [];
        $existsIsoCodes = [];
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt'));
        $currencies = $this->currencyRepository->search($criteria, $context)->getEntities();

        foreach ($currencies as $currency) {
            if (isset($existsIsoCodes[$currency->getIsoCode()])) {
                $currenciesToDelete[] = ['id' => $currency->getId()];
                continue;
            }

            $existsIsoCodes[$currency->getIsoCode()] = $currency->getId();
        }

        if (empty($currenciesToDelete)) {
            return;
        }

        $this->currencyRepository->delete($currenciesToDelete, $context);
    }
}
