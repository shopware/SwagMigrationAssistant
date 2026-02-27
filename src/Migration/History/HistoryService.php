<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\History;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\Bucket;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('fundamentals@after-sales')]
class HistoryService implements HistoryServiceInterface
{
    public const LOG_FETCH_LIMIT = 50;
    public const LOG_TIME_FORMAT = 'Y-m-d H:i:s T';

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $runRepo
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly EntityRepository $runRepo,
    ) {
    }

    public function getGroupedLogsOfRun(
        string $runUuid,
        Context $context,
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));

        $criteria->addAggregation(
            new TermsAggregation(
                'count',
                'code',
                null,
                null,
                new TermsAggregation(
                    'level',
                    'level'
                )
            )
        );

        $result = $this->loggingRepo->aggregate($criteria, $context);

        /** @var TermsResult $termsResult */
        $termsResult = $result->get('count');
        $aggregateResult = $termsResult->getBuckets();

        if (\count($aggregateResult) < 1) {
            return [];
        }

        $cleanResult = [];

        foreach ($aggregateResult as $bucket) {
            $detailInformation = $this->extractBucketInformation($bucket);
            $cleanResult[] = $detailInformation;
        }

        return $cleanResult;
    }

    /**
     * {@inheritdoc}
     */
    public function downloadLogsOfRun(string $runUuid, Context $context): \Closure
    {
        $run = $this->getMigrationRun($runUuid, $context);

        if ($run === null) {
            throw MigrationException::entityNotExists(
                SwagMigrationRunEntity::class,
                $runUuid
            );
        }

        $total = $this->getTotalLogCount($runUuid, $context);

        return function () use ($run, $runUuid, $total, $context): void {
            $offset = 0;

            \printf('%s', $this->getPrefixLogInformation($run));

            if ($total === 0) {
                \printf('%sNo log entries found for this migration run.%s', \PHP_EOL, \PHP_EOL);
            }

            while ($offset < $total) {
                $logChunk = $this->getLogChunk($runUuid, $offset, $context);

                foreach ($logChunk->getElements() as $logEntry) {
                    if (!$logEntry instanceof SwagMigrationLoggingEntity) {
                        continue;
                    }

                    $this->printLogEntry($logEntry);
                }

                $offset += self::LOG_FETCH_LIMIT;
            }
        };
    }

    private function printLogEntry(SwagMigrationLoggingEntity $logEntry): void
    {
        \printf('----- Log Entry #%d -----%s', $logEntry->getAutoIncrement(), \PHP_EOL);
        \printf('ID: %s%s', $logEntry->getId(), \PHP_EOL);
        \printf('Level: %s%s', $logEntry->getLevel(), \PHP_EOL);
        \printf('Code: %s%s', $logEntry->getCode(), \PHP_EOL);
        \printf('Profile name: %s%s', $logEntry->getProfileName(), \PHP_EOL);
        \printf('Gateway name: %s%s', $logEntry->getGatewayName(), \PHP_EOL);
        \printf('Created at: %s%s', $logEntry->getCreatedAt()?->format(self::LOG_TIME_FORMAT) ?? '-', \PHP_EOL);

        if ($logEntry->getEntityName()) {
            \printf('Entity: %s%s', $logEntry->getEntityName(), \PHP_EOL);
        }

        if ($logEntry->getFieldName()) {
            \printf('Field: %s%s', $logEntry->getFieldName(), \PHP_EOL);
        }

        if ($logEntry->getFieldSourcePath()) {
            \printf('Source path: %s%s', $logEntry->getFieldSourcePath(), \PHP_EOL);
        }

        if ($logEntry->getExceptionMessage()) {
            \printf('Exception message: %s%s', $logEntry->getExceptionMessage(), \PHP_EOL);
        }

        if ($logEntry->getSourceData()) {
            \printf('Source data (JSON):%s%s%s', \PHP_EOL, \json_encode($logEntry->getSourceData(), \JSON_PRETTY_PRINT) ?: '{}', \PHP_EOL);
        }

        if ($logEntry->getConvertedData()) {
            \printf('Converted data (JSON):%s%s%s', \PHP_EOL, \json_encode($logEntry->getConvertedData(), \JSON_PRETTY_PRINT) ?: '{}', \PHP_EOL);
        }

        if ($logEntry->getExceptionTrace()) {
            \printf('Exception trace (JSON):%s%s%s', \PHP_EOL, \json_encode($logEntry->getExceptionTrace(), \JSON_PRETTY_PRINT) ?: '{}', \PHP_EOL);
        }

        \printf(\PHP_EOL);
    }

    private function extractBucketInformation(Bucket $bucket): array
    {
        /** @var TermsResult|null $levelResult */
        $levelResult = $bucket->getResult();
        $levelString = '';

        if ($levelResult !== null) {
            $levelBuckets = $levelResult->getBuckets();

            if (!empty($levelBuckets)) {
                $levelString = $levelBuckets[0]->getKey();
            }
        }

        return [
            'code' => $bucket->getKey(),
            'count' => $bucket->getCount(),
            'level' => $levelString,
        ];
    }

    private function getTotalLogCount(string $runUuid, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addAggregation(new CountAggregation('count', 'id'));

        $result = $this->loggingRepo->aggregate($criteria, $context);

        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
    }

    private function getMigrationRun(string $runUuid, Context $context): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria([$runUuid]);
        $criteria->addAssociation('connection');

        return $this->runRepo->search($criteria, $context)->getEntities()->get($runUuid);
    }

    /**
     * @return EntityCollection<SwagMigrationLoggingEntity>
     */
    private function getLogChunk(string $runUuid, int $offset, Context $context): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addFilter(new EqualsFilter('userFixable', 0));
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING));
        $criteria->setOffset($offset);
        $criteria->setLimit(self::LOG_FETCH_LIMIT);

        return $this->loggingRepo->search($criteria, $context)->getEntities();
    }

    private function getPrefixLogInformation(SwagMigrationRunEntity $run): string
    {
        $connection = $run->getConnection();
        $profileName = '-';
        $gatewayName = '-';
        $connectionName = '-';

        $premapping = 'Associated connection not found';

        if ($connection !== null) {
            $connectionName = $connection->getName();
            $profileName = $connection->getProfileName();
            $gatewayName = $connection->getGatewayName();
            $premapping = $connection->getPremapping();
        }

        $updatedAt = $run->getUpdatedAt()?->format(self::LOG_TIME_FORMAT) ?? '-';
        $createdAt = $run->getCreatedAt()?->format(self::LOG_TIME_FORMAT) ?? '-';

        return \sprintf(
            '########## MIGRATION LOG ##########' . \PHP_EOL . \PHP_EOL
            . '########## RUN INFORMATION ##########' . \PHP_EOL
            . 'Generated at: %s' . \PHP_EOL
            . 'Run ID: %s' . \PHP_EOL
            . 'Status: %s' . \PHP_EOL
            . 'Created at: %s' . \PHP_EOL
            . 'Updated at: %s' . \PHP_EOL . \PHP_EOL
            . '########## CONNECTION INFORMATION ##########' . \PHP_EOL
            . 'Connection ID: %s' . \PHP_EOL
            . 'Connection name: %s' . \PHP_EOL
            . 'Profile name: %s' . \PHP_EOL
            . 'Gateway name: %s' . \PHP_EOL . \PHP_EOL
            . '########## SELECTED DATASETS ##########' . \PHP_EOL
            . '%s' . \PHP_EOL
            . '########## ADDITIONAL METADATA ##########' . \PHP_EOL
            . 'Environment information (JSON):' . \PHP_EOL . '%s' . \PHP_EOL . \PHP_EOL
            . 'Pre-mapping (JSON):' . \PHP_EOL . '%s' . \PHP_EOL . \PHP_EOL
            . '########## LOG ENTRIES ##########' . \PHP_EOL,
            \date(self::LOG_TIME_FORMAT),
            $run->getId(),
            $run->getStepValue(),
            $createdAt,
            $updatedAt,
            $run->getConnectionId() ?? '-',
            $connectionName,
            $profileName,
            $gatewayName,
            $this->getFormattedSelectedDataSets($run->getProgress()),
            \json_encode($run->getEnvironmentInformation(), \JSON_PRETTY_PRINT),
            \json_encode($premapping, \JSON_PRETTY_PRINT)
        );
    }

    private function getFormattedSelectedDataSets(?MigrationProgress $progress): string
    {
        if ($progress === null || $progress->getDataSets()->count() < 1) {
            return 'No datasets selected.' . \PHP_EOL;
        }

        $output = '';

        foreach ($progress->getDataSets() as $dataSet) {
            $output .= \sprintf('- %s (Total: %d)' . \PHP_EOL, $dataSet->getEntityName(), $dataSet->getTotal());
        }

        return $output;
    }
}
