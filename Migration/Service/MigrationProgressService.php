<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Aggregation\CountAggregation;
use Shopware\Core\Framework\ORM\Search\Aggregation\ValueCountAggregation;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\NestedQuery;
use Shopware\Core\Framework\ORM\Search\Query\NotQuery;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\ORM\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\Run\SwagMigrationRunStruct;

class MigrationProgressService implements MigrationProgressServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepository;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepository;

    /**
     * @var RepositoryInterface
     */
    private $migrationMediaFileRepository;

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        RepositoryInterface $migrationRunRepository,
        RepositoryInterface $migrationDataRepository,
        RepositoryInterface $migrationMediaFileRepository
    ) {
        $this->migrationRunRepository = $migrationRunRepository;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->migrationMediaFileRepository = $migrationMediaFileRepository;
    }

    public function getProgress(Context $context): ProgressState
    {
        $this->context = $context;

        //Get the last migration run
        $criteria = new Criteria();
        $criteria->addAssociation('profile');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1); //TODO: maybe use this method for different runs to get the migration progress.
        $result = $this->migrationRunRepository->search($criteria, $this->context);

        if ($result->getTotal() === 0) {
            return new ProgressState(false);
        }

        /** @var SwagMigrationRunStruct $run */
        $run = $result->first();

        if ($run->getStatus() !== SwagMigrationRunStruct::STATUS_RUNNING) {
            return new ProgressState(false);
        }

        //Get the current entity counts
        $totals = $run->getTotals();
        $fetchedEntityCounts = $this->getEntityCounts($run->getId(), false);
        $writtenEntityCounts = $this->getEntityCounts($run->getId());

        //compare fetched counts
        foreach ($totals['toBeFetched'] as $entity => $count) {
            if ($fetchedEntityCounts[$entity] < $count) {
                //if the run currently fetching data, discard that run and set the status to aborted
                $this->migrationRunRepository->update([
                    [
                        'id' => $run->getId(),
                        'status' => SwagMigrationRunStruct::STATUS_ABORTED,
                    ],
                ], $this->context);

                return new ProgressState(false);
            }
        }

        //check if the run finished fetching, but not started writing yet
        if (!isset($totals['toBeWritten'])) {
            reset($totals['toBeFetched']);
            $progressState = new ProgressState(
                true,
                $run->getId(),
                $run->getProfile()->jsonSerialize(),
                ProgressState::STATUS_WRITE_DATA,
                key($totals['toBeFetched']),
                0
            );

            return $this->buildEntityGroups($run, $progressState, $fetchedEntityCounts);
        }

        //compare written counts
        $currentEntity = null;
        foreach ($totals['toBeWritten'] as $entity => $count) {
            if (!isset($writtenEntityCounts[$entity])) {
                $progressState = new ProgressState(
                    true,
                    $run->getId(),
                    $run->getProfile()->jsonSerialize(),
                    ProgressState::STATUS_WRITE_DATA,
                    $entity,
                    0,
                    $count
                );

                return $this->buildEntityGroups($run, $progressState, $writtenEntityCounts);
            }

            if ($writtenEntityCounts[$entity] < $count) {
                $progressState = new ProgressState(
                    true,
                    $run->getId(),
                    $run->getProfile()->jsonSerialize(),
                    ProgressState::STATUS_WRITE_DATA,
                    $entity,
                    $writtenEntityCounts[$entity],
                    $count
                );

                return $this->buildEntityGroups($run, $progressState, $writtenEntityCounts);
            }
        }

        $totalMediaFileCount = $this->getMediaFileCounts($run->getId(), false);
        $downloadedMediaFileCount = $this->getMediaFileCounts($run->getId());

        if ($downloadedMediaFileCount < $totalMediaFileCount) {
            $progressState = new ProgressState(
                true,
                $run->getId(),
                $run->getProfile()->jsonSerialize(),
                ProgressState::STATUS_DOWNLOAD_DATA,
                'media',
                $downloadedMediaFileCount,
                $totalMediaFileCount
            );

            return $this->buildEntityGroups($run, $progressState, ['media' => $downloadedMediaFileCount]);
        }

        return new ProgressState(false);
    }

    private function getEntityCounts(string $runId, bool $isWritten = true): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $runId));
        if ($isWritten) {
            $criteria->addFilter(new TermQuery('written', true));
        }
        $criteria->addAggregation(new ValueCountAggregation('entity', 'entityCount'));
        $result = $this->migrationDataRepository->search($criteria, $this->context);
        $counts = $result->getAggregations()->first()->getResult();

        //convert counts from string to int
        return $this->mapCounts($counts);
    }

    private function getMediaFileCounts(string $runId, bool $onlyDownloaded = true): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $runId));
        $criteria->addFilter(new TermQuery('written', true));
        if ($onlyDownloaded) {
            $criteria->addFilter(new TermQuery('downloaded', true));
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

    private function buildEntityGroups(SwagMigrationRunStruct $run, ProgressState $state, array $finishedCount): ProgressState
    {
        $additionalData = $run->getAdditionalData();
        $entityGroups = $additionalData['entityGroups'];

        if ($state->getStatus() === ProgressState::STATUS_WRITE_DATA) {
            //get totalCounts for write (database totals does not have the total count for every entity in 'toBeWritten'!)
            $criteria = new Criteria();
            $criteria->addAggregation(new ValueCountAggregation('entity', 'entityCount'));
            $criteria->addFilter(new NestedQuery([
                new TermQuery('runId', $run->getId()),
                new NotQuery([
                    new TermQuery('converted', null),
                ]),
            ]));
            $result = $this->migrationDataRepository->search($criteria, $this->context);
            $totalCountsForWriting = $result->getAggregations()->first()->getResult();
            $totalCountsForWriting = $this->mapCounts($totalCountsForWriting);

            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, $totalCountsForWriting);
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
            $group['count'] = $groupTotalsCount;
            $group['progress'] = $groupFinishedCount;
        }

        return $entityGroups;
    }
}
