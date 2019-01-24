<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\ValueCountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\Run\SwagMigrationAccessTokenService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
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

    public function __construct(
        EntityRepositoryInterface $migrationRunRepository,
        EntityRepositoryInterface $migrationDataRepository,
        EntityRepositoryInterface $migrationMediaFileRepository,
        SwagMigrationAccessTokenService $migrationAccessTokenService
    ) {
        $this->migrationRunRepository = $migrationRunRepository;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->migrationMediaFileRepository = $migrationMediaFileRepository;
        $this->migrationAccessTokenService = $migrationAccessTokenService;
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
        $totals = $run->getTotals();
        $fetchedEntityCounts = $this->getEntityCounts($run->getId(), false);

        if ($totals === null || $fetchedEntityCounts == null) {
            return new ProgressState(true, $this->validMigrationAccessToken);
        }

        // Compare fetched counts
        $compareFetchCountResult = $this->compareFetchCount($run, $totals, $fetchedEntityCounts);
        if ($compareFetchCountResult !== null) {
            return $compareFetchCountResult;
        }

        // Check if the run finished fetching, but not started writing yet
        $writeNotStartedResult = $this->isWriteNotStartedResult($run, $totals, $fetchedEntityCounts);
        if ($writeNotStartedResult !== null) {
            return $writeNotStartedResult;
        }

        // Compare written counts
        $writtenEntityCounts = $this->getEntityCounts($run->getId());
        $compareWrittenCountResult = $this->compareWrittenCount($run, $totals, $writtenEntityCounts);
        if ($compareWrittenCountResult !== null) {
            return $compareWrittenCountResult;
        }

        // Compare media download counts
        $compareMediaDownloadCountResult = $this->compareMediaDownloadCount($run);
        if ($compareMediaDownloadCountResult !== null) {
            return $compareMediaDownloadCountResult;
        }

        return new ProgressState(false, $this->validMigrationAccessToken);
    }

    private function getCurrentRun(): ?SwagMigrationRunEntity
    {
        // Get the last migration run
        $criteria = new Criteria();
        $criteria->addAssociation('profile');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1); //TODO: maybe use this method for different runs to get the migration progress.
        $result = $this->migrationRunRepository->search($criteria, $this->context);

        if ($result->getTotal() === 0) {
            return null;
        }

        /* @var SwagMigrationRunEntity $run */
        return $result->first();
    }

    private function getEntityCounts(string $runId, bool $isWritten = true): array
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
        $result = $this->migrationDataRepository->search($criteria, $this->context);
        $counts = $result->getAggregations()->first()->getResult();

        // Convert counts from string to int
        return $this->mapCounts($counts);
    }

    private function getMediaFileCounts(string $runId, bool $onlyDownloaded = true): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('written', true));
        if ($onlyDownloaded) {
            $criteria->addFilter(new EqualsFilter('downloaded', true));
        }
        $criteria->addAggregation(new CountAggregation('id', 'count'));
        $criteria->setLimit(1);
        $result = $this->migrationMediaFileRepository->search($criteria, $this->context);

        return (int) $result->getAggregations()->first()->getResult()['count'];
    }

    private function mapCounts($counts): array
    {
        $mappedCounts = [];
        foreach ($counts as $item) {
            $mappedCounts[$item['key']] = (int) $item['count'];
        }

        return $mappedCounts;
    }

    private function buildEntityGroups(SwagMigrationRunEntity $run, ProgressState $state, array $finishedCount): ProgressState
    {
        $additionalData = $run->getAdditionalData();
        $entityGroups = $additionalData['entityGroups'];

        if ($state->getStatus() === ProgressState::STATUS_WRITE_DATA) {
            // Get totalCounts for write (database totals does not have the total count for every entity in 'toBeWritten'!)
            $criteria = new Criteria();
            $criteria->addAggregation(new ValueCountAggregation('entity', 'entityCount'));
            $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('runId', $run->getId()),
                new NotFilter(NotFilter::CONNECTION_AND, [
                    new EqualsFilter('converted', null),
                ]),
            ]));
            $result = $this->migrationDataRepository->search($criteria, $this->context);
            $totalCountsForWriting = $result->getAggregations()->first()->getResult();
            $totalCountsForWriting = $this->mapCounts($totalCountsForWriting);

            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, $totalCountsForWriting);
        //todo: Verify if this is necessary
        } elseif ($state->getStatus() === ProgressState::STATUS_FETCH_DATA) {
            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, $run->getTotals()['toBeFetched']);
        } elseif ($state->getStatus() === ProgressState::STATUS_DOWNLOAD_DATA) {
            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, ['media' => $state->getEntityCount()]);
        }

        $state->setEntityGroups($entityGroups);

        return $state;
    }

    private function validateEntityGroupCounts(array $entityGroups, array $finishedCount, array $totalCount): array
    {
        foreach ($entityGroups as &$group) {
            $groupTotalsCount = 0;
            $groupFinishedCount = 0;
            foreach ($group['entities'] as &$entity) {
                $entityName = $entity['entityName'];
                if (isset($totalCount[$entityName])) {
                    $entity['entityCount'] = $totalCount[$entityName];
                }

                $groupTotalsCount += $entity['entityCount'];
                if (isset($finishedCount[$entityName])) {
                    $groupFinishedCount += $finishedCount[$entityName];
                }
            }
            unset($entity);

            $group['count'] = $groupTotalsCount;
            $group['progress'] = $groupFinishedCount;
        }

        return $entityGroups;
    }

    /**
     * Compares the current fetch counts with the counts to fetch and returns the ProgressState if necessary
     */
    private function compareFetchCount(SwagMigrationRunEntity $run, array $totals, array $fetchedEntityCounts): ?ProgressState
    {
        foreach ($totals['toBeFetched'] as $entity => $count) {
            if ($count === 0) {
                continue;
            }

            if (!isset($fetchedEntityCounts[$entity]) ||
                (isset($fetchedEntityCounts[$entity]) && $fetchedEntityCounts[$entity] < $count)
            ) {
                return new ProgressState(false, $this->validMigrationAccessToken, null, null, ProgressState::STATUS_FETCH_DATA);
            }
        }

        return null;
    }

    /**
     * Compares the current written counts with the counts to write and returns the ProgressState if necessary
     */
    private function compareWrittenCount(SwagMigrationRunEntity $run, array $totals, array $writtenEntityCounts): ?ProgressState
    {
        foreach ($totals['toBeWritten'] as $entity => $count) {
            if ($count === 0 ||
                (isset($writtenEntityCounts[$entity]) && $writtenEntityCounts[$entity] >= $count)
            ) {
                continue;
            }

            if (isset($writtenEntityCounts[$entity])) {
                $finishCount = $writtenEntityCounts[$entity];
            } else {
                $finishCount = 0;
            }

            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                $run->getProfile()->jsonSerialize(),
                ProgressState::STATUS_WRITE_DATA,
                $entity,
                $finishCount,
                $count
            );

            return $this->buildEntityGroups($run, $progressState, $writtenEntityCounts);
        }

        return null;
    }

    /**
     * Compares the current download counts with the counts to download and returns the ProgressState if necessary
     */
    private function compareMediaDownloadCount(SwagMigrationRunEntity $run): ?ProgressState
    {
        $totalMediaFileCount = $this->getMediaFileCounts($run->getId(), false);
        $downloadedMediaFileCount = $this->getMediaFileCounts($run->getId());

        if ($downloadedMediaFileCount < $totalMediaFileCount) {
            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                $run->getProfile()->jsonSerialize(),
                ProgressState::STATUS_DOWNLOAD_DATA,
                'media',
                $downloadedMediaFileCount,
                $totalMediaFileCount
            );

            return $this->buildEntityGroups($run, $progressState, ['media' => $downloadedMediaFileCount]);
        }

        return null;
    }

    /**
     * Returns the ProgressState if all datasets are fetched, but the writing not started yet.
     */
    private function isWriteNotStartedResult(SwagMigrationRunEntity $run, array $totals, array $fetchedEntityCounts): ?ProgressState
    {
        if (!isset($totals['toBeWritten'])) {
            reset($totals['toBeFetched']);
            $progressState = new ProgressState(
                true,
                $this->validMigrationAccessToken,
                $run->getId(),
                $run->getProfile()->jsonSerialize(),
                ProgressState::STATUS_WRITE_DATA,
                key($totals['toBeFetched']),
                0
            );

            return $this->buildEntityGroups($run, $progressState, $fetchedEntityCounts);
        }

        return null;
    }
}
