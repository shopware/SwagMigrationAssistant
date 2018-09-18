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
        RepositoryInterface $migrationMediaFileRepository)
    {
        $this->migrationRunRepository = $migrationRunRepository;
        $this->migrationDataRepository = $migrationDataRepository;
        $this->migrationMediaFileRepository = $migrationMediaFileRepository;
    }

    public function getProgress(Context $context): array
    {
        $this->context = $context;

        //Get the last migration run
        $criteria = new Criteria();
        $criteria->addAssociation('profile');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1); //TODO: maybe use this method for different runs to get the migration progress.
        $result = $this->migrationRunRepository->search($criteria, $this->context);

        if ($result->getTotal() === 0) {
            return [
                'migrationRunning' => false,
            ];
        }

        /** @var SwagMigrationRunStruct $run */
        $run = $result->first();
        $totals = $run->getTotals();

        if ($run->getStatus() !== SwagMigrationRunStruct::STATUS_RUNNING) {
            return [
                'migrationRunning' => false,
            ];
        }

        //Get the current entity counts
        $fetchedEntityCounts = $this->getEntityCounts($run->getId(), false);
        $writtenEntityCounts = $this->getEntityCounts($run->getId(), true);

        //compare fetched counts
        foreach ($totals['toBeFetched'] as $entity => $count) {
            if ($fetchedEntityCounts[$entity] < $count) {
                //if we are currently fetching we want to discard that run and set the status to aborted
                $run->setStatus(SwagMigrationRunStruct::STATUS_ABORTED);
                $this->migrationRunRepository->update([
                    [
                        'id' => $run->getId(),
                        'status' => $run->getStatus(),
                    ],
                ], $this->context);

                return [
                    'migrationRunning' => false,
                ];
            }
        }

        //check if we have finished fetching but not startet writing yet
        if (!isset($totals['toBeWritten'])) {
            reset($totals['toBeFetched']);

            return $this->buildState($run, [
                'migrationRunning' => true,
                'status' => 'writeData',
                'entity' => key($totals['toBeFetched']),
                'finishedCount' => 0,
            ], $fetchedEntityCounts);
        }

        //compare written counts
        foreach ($totals['toBeWritten'] as $entity => $count) {
            if (!isset($writtenEntityCounts[$entity])) {
                return $this->buildState($run, [
                    'migrationRunning' => true,
                    'status' => 'writeData',
                    'entity' => $entity,
                    'finishedCount' => 0,
                    'entityCount' => $count,
                ], $writtenEntityCounts);
            }

            if ($writtenEntityCounts[$entity] < $count) {
                return $this->buildState($run, [
                    'migrationRunning' => true,
                    'status' => 'writeData',
                    'entity' => $entity,
                    'finishedCount' => $writtenEntityCounts[$entity],
                    'entityCount' => $count,
                ], $writtenEntityCounts);
            }
        }

        $totalMediaFileCount = $this->getMediaFileCounts($run->getId(), false);
        $downloadedMediaFileCount = $this->getMediaFileCounts($run->getId(), true);

        if ($downloadedMediaFileCount < $totalMediaFileCount) {
            return $this->buildState($run, [
                'migrationRunning' => true,
                'status' => 'downloadData',
                'entity' => 'media',
                'finishedCount' => $downloadedMediaFileCount,
                'entityCount' => $totalMediaFileCount,
            ], [
                'media' => $downloadedMediaFileCount,
            ]);
        }

        return [
            'migrationRunning' => false,
        ];
    }

    private function getEntityCounts(string $runId, bool $isWritten): array
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

    private function getMediaFileCounts(string $runId, bool $onlyDownloaded): int
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

    private function mapCounts($counts)
    {
        $mappedCounts = [];
        foreach ($counts as $item) {
            $mappedCounts[$item['key']] = (int) $item['count'];
        }

        return $mappedCounts;
    }

    private function buildState(SwagMigrationRunStruct $run, array $state, array $finishedCount): array
    {
        $additionalData = $run->getAdditionalData();
        $entityGroups = $additionalData['entityGroups'];

        if ($state['status'] === 'writeData') {
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
        } elseif ($state['status'] === 'fetchData') {
            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, $run->getTotals()['toBeFetched']);
        } elseif ($state['status'] === 'downloadData') {
            $entityGroups = $this->validateEntityGroupCounts($entityGroups, $finishedCount, ['media' => $state['entityCount']]);
        }

        $state['profile'] = $run->getProfile();
        $state['runId'] = $run->getId();
        $state['entityGroups'] = $entityGroups;

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
