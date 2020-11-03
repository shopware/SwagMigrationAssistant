<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\Bucket;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\HttpFoundation\Request;

class MigrationProgressService implements MigrationProgressServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationMediaFileRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var SwagMigrationAccessTokenService
     */
    private $migrationAccessTokenService;

    /**
     * @var bool
     */
    private $validMigrationAccessToken = false;

    /**
     * @var RunServiceInterface
     */
    private $runService;

    /**
     * @var PremappingServiceInterface
     */
    private $premappingService;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepository,
        EntityRepositoryInterface $migrationDataRepository,
        EntityRepositoryInterface $migrationMediaFileRepository,
        SwagMigrationAccessTokenService $migrationAccessTokenService,
        RunServiceInterface $runService,
        PremappingServiceInterface $premappingService,
        MigrationContextFactoryInterface $migrationContextFactory
    ) {
        $this->migrationRunRepository = $migrationRunRepository;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->migrationMediaFileRepository = $migrationMediaFileRepository;
        $this->migrationAccessTokenService = $migrationAccessTokenService;
        $this->runService = $runService;
        $this->premappingService = $premappingService;
        $this->migrationContextFactory = $migrationContextFactory;
    }

    public function getProgress(Request $request, Context $context): ProgressState
    {
        $this->context = $context;
        $run = $this->getCurrentRun();

        if ($run === null || $run->getStatus() !== SwagMigrationRunEntity::STATUS_RUNNING) {
            return new ProgressState(false, true);
        }

        $this->validMigrationAccessToken = $this->migrationAccessTokenService->validateMigrationAccessToken($run->getId(), $request, $context);

        // Get the current entity counts
        $progress = $run->getProgress();
        if ($progress === null) {
            $progress = [];
        }
        $totals = $this->getTotals($progress);
        $fetchedEntityCounts = $this->runService->calculateCurrentTotals($run->getId(), false, $context);

        if (empty($totals) || empty($fetchedEntityCounts)) {
            if ($this->validMigrationAccessToken) {
                $this->abortProcessingRun($run, $context);

                return new ProgressState(
                    false,
                    $this->validMigrationAccessToken,
                    $run->getId(),
                    null,
                    ProgressState::STATUS_FETCH_DATA,
                    null,
                    0,
                    0,
                    $progress
                );
            }

            $migrationContext = $this->migrationContextFactory->create($run);

            $premapping = null;
            if ($migrationContext !== null) {
                $premapping = $this->premappingService->generatePremapping($context, $migrationContext, $run);
            }

            $status = ProgressState::STATUS_PREMAPPING;
            if (empty($premapping)) {
                $status = ProgressState::STATUS_FETCH_DATA;
            }

            return new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                null,
                $status,
                null,
                0,
                0,
                $progress
            );
        }

        // Compare fetched counts
        $compareFetchCountResult = $this->compareFetchCount($run, $totals, $fetchedEntityCounts);
        if ($compareFetchCountResult !== null) {
            return $compareFetchCountResult;
        }

        // Compare written counts
        $writtenEntityCounts = $this->runService->calculateCurrentTotals($run->getId(), true, $context);
        $compareWrittenCountResult = $this->compareWrittenCount($run, $totals, $writtenEntityCounts);
        if ($compareWrittenCountResult !== null) {
            return $compareWrittenCountResult;
        }

        // Compare media download counts
        $compareMediaDownloadCountResult = $this->compareMediaProcessedCount($run);
        if ($compareMediaDownloadCountResult !== null) {
            return $compareMediaDownloadCountResult;
        }

        $progress = $run->getProgress();
        if ($progress === null) {
            $progress = [];
        }

        return new ProgressState(
            false,
            $this->validMigrationAccessToken,
            $run->getId(),
            null,
            ProgressState::STATUS_WAITING,
            null,
            0,
            0,
            $progress
        );
    }

    private function abortProcessingRun(SwagMigrationRunEntity $run, Context $context): void
    {
        $this->migrationRunRepository->update(
            [
                [
                    'id' => $run->getId(),
                    'status' => SwagMigrationRunEntity::STATUS_ABORTED,
                ],
            ],
            $context
        );
    }

    private function getCurrentRun(): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);
        $result = $this->migrationRunRepository->search($criteria, $this->context);

        if ($result->getTotal() === 0) {
            return null;
        }

        /* @var SwagMigrationRunEntity $run */
        return $result->first();
    }

    private function getMediaFileCounts(string $runId, bool $onlyProcessed = true): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('written', true));
        if ($onlyProcessed) {
            $criteria->addFilter(new EqualsFilter('processed', true));
        }
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);
        $result = $this->migrationMediaFileRepository->aggregate($criteria, $this->context);
        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
    }

    /**
     * @param Bucket[] $counts
     *
     * @return int[]
     */
    private function mapCounts(array $counts): array
    {
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

    private function buildEntityGroups(SwagMigrationRunEntity $run, ProgressState $state, array $finishedCount): ProgressState
    {
        $runProgress = $run->getProgress();

        if ($runProgress === null) {
            return new ProgressState(
                false,
                false,
                $run->getId()
            );
        }

        if ($state->getStatus() === ProgressState::STATUS_WRITE_DATA) {
            // Get totalCounts for write (database totals does not have the total count for every entity in 'toBeWritten'!)
            $criteria = new Criteria();
            $criteria->addAggregation(new TermsAggregation('entityCount', 'entity'));
            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('runId', $run->getId()),
                new NotFilter(NotFilter::CONNECTION_AND, [
                    new EqualsFilter('converted', null),
                ]),
            ]));
            $result = $this->migrationDataRepository->aggregate($criteria, $this->context);
            /** @var TermsResult $termsResult */
            $termsResult = $result->get('entityCount');
            $totalCountsForWriting = $termsResult->getBuckets();
            $totalCountsForWriting = $this->mapCounts($totalCountsForWriting);

            $runProgress = $this->validateEntityGroupCounts($runProgress, $finishedCount, $totalCountsForWriting);
        } elseif ($state->getStatus() === ProgressState::STATUS_DOWNLOAD_DATA) {
            $runProgress = $this->validateEntityGroupCounts($runProgress, $finishedCount, ['media' => $state->getEntityCount()]);
        }

        $state->setRunProgress($runProgress);

        return $state;
    }

    private function validateEntityGroupCounts(array $runProgressArray, array $finishedCount, array $totalCount): array
    {
        foreach ($runProgressArray as &$runProgress) {
            $groupTotalsCount = 0;
            $groupFinishedCount = 0;

            foreach ($runProgress['entities'] as &$entity) {
                $entityName = $entity['entityName'];
                if (isset($totalCount[$entityName])) {
                    $entity['total'] = $totalCount[$entityName];
                }

                $groupTotalsCount += $entity['total'];
                if (isset($finishedCount[$entityName])) {
                    $entity['currentCount'] = $finishedCount[$entityName];
                    $groupFinishedCount += $finishedCount[$entityName];
                }
            }
            unset($entity);

            $runProgress['total'] = $groupTotalsCount;
            $runProgress['currentCount'] = $groupFinishedCount;
        }

        return $runProgressArray;
    }

    /**
     * Compares the current fetch counts with the counts to fetch and returns the ProgressState if necessary
     */
    private function compareFetchCount(SwagMigrationRunEntity $run, array $totals, array $fetchedEntityCounts): ?ProgressState
    {
        $entryCount = \count($totals);
        $maxKey = $entryCount - 1;
        $totalsWithoutIndex = \array_values($totals);
        $keysOfTotal = \array_keys($totals);
        $runProgress = $run->getProgress();

        if ($entryCount === 0 || $runProgress === null) {
            return null;
        }

        for ($currentKey = $maxKey; $currentKey > 0; --$currentKey) {
            $count = $totalsWithoutIndex[$currentKey];
            $entity = $keysOfTotal[$currentKey];

            if ($count === 0) {
                continue;
            }

            if (!isset($fetchedEntityCounts[$entity])
                || (isset($fetchedEntityCounts[$entity]) && $fetchedEntityCounts[$entity] === 0)
            ) {
                continue;
            }

            if ($fetchedEntityCounts[$entity] >= $count && $currentKey === $maxKey) {
                return null;
            }

            if ($fetchedEntityCounts[$entity] === $count) {
                $key = ++$currentKey;
                if ($currentKey > $maxKey) {
                    $key = $maxKey;
                }

                $count = $totalsWithoutIndex[$key];
                $entity = $keysOfTotal[$key];
            }

            $finishCount = $fetchedEntityCounts[$entity] ?? 0;

            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                null,
                ProgressState::STATUS_FETCH_DATA,
                $entity,
                $finishCount,
                $count,
                $runProgress
            );

            $runProgress = $this->validateEntityGroupCounts($runProgress, $fetchedEntityCounts, $totals);
            $progressState->setRunProgress($runProgress);

            return $progressState;
        }

        $count = $totalsWithoutIndex[0];
        $entity = $keysOfTotal[0];
        $finishCount = $fetchedEntityCounts[0] ?? 0;

        $progressState = new ProgressState(
            true,
            $this->validMigrationAccessToken,
            $run->getId(),
            null,
            ProgressState::STATUS_FETCH_DATA,
            $entity,
            $finishCount,
            $count,
            $runProgress
        );

        $runProgress = $this->validateEntityGroupCounts($runProgress, $fetchedEntityCounts, $totals);
        $progressState->setRunProgress($runProgress);

        return $progressState;
    }

    /**
     * Compares the current written counts with the counts to write and returns the ProgressState if necessary
     */
    private function compareWrittenCount(SwagMigrationRunEntity $run, array $totals, array $writtenEntityCounts): ?ProgressState
    {
        $progress = $run->getProgress();
        if ($progress === null) {
            return null;
        }

        foreach ($totals as $entity => $count) {
            if ($count === 0
                || (isset($writtenEntityCounts[$entity]) && $writtenEntityCounts[$entity] >= $count)
            ) {
                continue;
            }

            $finishCount = $writtenEntityCounts[$entity] ?? 0;

            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                null,
                ProgressState::STATUS_WRITE_DATA,
                $entity,
                $finishCount,
                $count,
                $progress
            );

            return $this->buildEntityGroups($run, $progressState, $writtenEntityCounts);
        }

        return null;
    }

    /**
     * Compares the current download counts with the counts to download and returns the ProgressState if necessary
     */
    private function compareMediaProcessedCount(SwagMigrationRunEntity $run): ?ProgressState
    {
        $progress = $run->getProgress();
        if ($progress === null) {
            return null;
        }

        $totalMediaFileCount = $this->getMediaFileCounts($run->getId(), false);
        $processedMediaFileCount = $this->getMediaFileCounts($run->getId());

        if ($processedMediaFileCount < $totalMediaFileCount) {
            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                null,
                ProgressState::STATUS_DOWNLOAD_DATA,
                'media',
                $processedMediaFileCount,
                $totalMediaFileCount,
                $progress
            );

            return $this->buildEntityGroups($run, $progressState, ['media' => $processedMediaFileCount]);
        }

        return null;
    }

    private function getTotals(array $runProgressArray): array
    {
        $totals = [];

        foreach ($runProgressArray as &$runProgress) {
            foreach ($runProgress['entities'] as &$entity) {
                if ($runProgress['id'] === 'processMediaFiles') {
                    continue;
                }

                $totals[$entity['entityName']] = $entity['total'];
            }
            unset($entity);
        }

        return $totals;
    }
}
