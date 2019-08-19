<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\History;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class HistoryService implements HistoryServiceInterface
{
    private const LOG_FETCH_LIMIT = 50;
    private const LOG_TIME_FORMAT = 'd.m.Y h:i:s e';

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    public function __construct(
        EntityRepositoryInterface $loggingRepo,
        EntityRepositoryInterface $runRepo
    ) {
        $this->loggingRepo = $loggingRepo;
        $this->runRepo = $runRepo;
    }

    public function getGroupedLogsOfRun(
        string $runUuid,
        int $offset,
        int $limit,
        string $sortBy,
        string $sortDirection,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_AND, [
                    new EqualsFilter('level', LogEntryInterface::LOG_LEVEL_INFO),
                ]
            )
        );
        $criteria->addAggregation(new CountAggregation('code', 'count', 'code'));

        // Currently not working, maybe it will never work - TODO: check if this works after core change (NEXT-4144)
        $criteria->addSorting(new FieldSorting($sortBy, $sortDirection));
        $criteria->setOffset($offset);
        $criteria->setLimit($limit);

        $aggregation = $this->loggingRepo->aggregate($criteria, $context)->getAggregations()->first();
        if ($aggregation === null) {
            return [];
        }

        $aggregateResult = $aggregation->getResult();
        $cleanResult = [];

        /** @var CountResult $countResult */
        foreach ($aggregateResult as $countResult) {
            $detailInformation = $this->getLogEntryInformationByCode($runUuid, $countResult, $context);
            if ($detailInformation !== null) {
                $cleanResult[] = $detailInformation;
            }
        }

        return $cleanResult;
    }

    /**
     * {@inheritdoc}
     */
    public function downloadLogsOfRun(string $runUuid, Context $context): \Closure
    {
        $offset = 0;
        $total = $this->getTotalLogCount($runUuid, $context);
        $run = $this->getMigrationRun($runUuid, $context);

        return function () use ($run, $runUuid, $offset, $total, $context) {
            printf('%s%s', $this->getPrefixLogInformation($run), PHP_EOL);

            while ($offset < $total) {
                /** @var SwagMigrationLoggingCollection $logChunk */
                $logChunk = $this->getLogChunk($runUuid, $offset, $context);

                foreach ($logChunk->getElements() as $logEntry) {
                    printf('[%s] %s%s', $logEntry->getLevel(), $logEntry->getCode(), PHP_EOL);
                    printf('%s%s', $logEntry->getTitle(), PHP_EOL);
                    printf('%s%s%s', $logEntry->getDescription(), PHP_EOL, PHP_EOL);
                }

                $offset += self::LOG_FETCH_LIMIT;
                ob_flush();
                flush();
            }

            printf('%s%s%s', $this->getSuffixLogInformation($run), PHP_EOL, PHP_EOL);
            ob_flush();
            flush();
        };
    }

    private function getTotalLogCount(string $runUuid, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addAggregation(new CountAggregation('runId', 'count'));

        /** @var AggregationResult $aggregation */
        $aggregation = $this->loggingRepo->aggregate($criteria, $context)->getAggregations()->first();
        if ($aggregation !== null) {
            /** @var CountResult[] $countResult */
            $countResult = $aggregation->getResult();

            if (count($countResult) === 1) {
                return $countResult[0]->getCount();
            }
        }

        return 0;
    }

    private function getMigrationRun(string $runUuid, Context $context): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria([$runUuid]);
        $criteria->addAssociation('connection');
        $run = $this->runRepo->search($criteria, $context)->getElements();
        if (!isset($run[$runUuid])) {
            return null;
        }

        return $run[$runUuid];
    }

    private function getLogEntryInformationByCode($runUuid, CountResult $countResult, Context $context): ?array
    {
        $code = $countResult->getKey()['code'];
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addFilter(new EqualsFilter('code', $code));
        $criteria->setLimit(1);

        /** @var SwagMigrationLoggingEntity|null $result */
        $result = $this->loggingRepo->search($criteria, $context)->first();
        if ($result === null) {
            return null;
        }

        return [
            'code' => $code,
            'count' => $countResult->getCount(),
            'titleSnippet' => $result->getTitleSnippet(),
            'parameters' => $result->getParameters(),
            'level' => $result->getLevel(),
        ];
    }

    private function getLogChunk($runUuid, int $offset, $context): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $criteria->setOffset($offset);
        $criteria->setLimit(self::LOG_FETCH_LIMIT);

        return $this->loggingRepo->search($criteria, $context)->getEntities();
    }

    private function getPrefixLogInformation(SwagMigrationRunEntity $run): string
    {
        return sprintf(
            'Migration log generated at %s' . PHP_EOL
            . 'Run id: %s' . PHP_EOL
            . 'Status: %s' . PHP_EOL
            . 'Created at: %s' . PHP_EOL
            . 'Updated at: %s' . PHP_EOL
            . 'Last control user id: %s' . PHP_EOL
            . 'Connection id: %s' . PHP_EOL
            . 'Connection name: %s' . PHP_EOL
            . 'Profile: %s' . PHP_EOL
            . 'Gateway: %s' . PHP_EOL . PHP_EOL
            . 'Selected data:' . PHP_EOL . '%s' . PHP_EOL
            . '--------------------Log-entries---------------------' . PHP_EOL,
            date(self::LOG_TIME_FORMAT),
            $run->getId(),
            $run->getStatus(),
            $run->getCreatedAt()->format(self::LOG_TIME_FORMAT),
            $run->getUpdatedAt() === null ? '-' : $run->getUpdatedAt()->format(self::LOG_TIME_FORMAT),
            $run->getUserId(),
            $run->getConnectionId(),
            $run->getConnection()->getName(),
            $run->getConnection()->getProfileName(),
            $run->getConnection()->getGatewayName(),
            $this->getFormattedSelectedData($run->getProgress())
        );
    }

    private function getFormattedSelectedData(?array $progress): string
    {
        if ($progress === null || count($progress) < 1) {
            return '';
        }

        $output = '';
        foreach ($progress as $group) {
            $output .= sprintf('- %s (total: %d)' . PHP_EOL, $group['id'], $group['total']);
            foreach ($group['entities'] as $entity) {
                $output .= sprintf(
                    "\t- %s (total: %d)" . PHP_EOL,
                    $entity['entityName'],
                    $entity['total']
                );
            }
        }

        return $output;
    }

    private function getSuffixLogInformation(SwagMigrationRunEntity $run): string
    {
        return sprintf(
            '--------------------Additional-metadata---------------------' . PHP_EOL
            . 'Environment information {JSON}:' . PHP_EOL . '%s' . PHP_EOL . PHP_EOL
            . 'Premapping {JSON}: ----------------------------------------------------' . PHP_EOL . '%s' . PHP_EOL,
            json_encode($run->getEnvironmentInformation(), JSON_PRETTY_PRINT),
            json_encode($run->getPremapping(), JSON_PRETTY_PRINT)
        );
    }
}
