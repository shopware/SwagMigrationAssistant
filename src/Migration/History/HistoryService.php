<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\History;

use Doctrine\DBAL\Connection;
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
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
class HistoryService implements HistoryServiceInterface
{
    private const LOG_FETCH_LIMIT = 50;
    private const LOG_TIME_FORMAT = 'd.m.Y h:i:s e';

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $runRepo
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly EntityRepository $runRepo,
        private readonly Connection $connection
    ) {
    }

    public function getGroupedLogsOfRun(
        string $runUuid,
        Context $context
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
                    'titleSnippet',
                    'titleSnippet',
                    null,
                    null,
                    new TermsAggregation(
                        'entity',
                        'entity',
                        null,
                        null,
                        new TermsAggregation(
                            'level',
                            'level'
                        )
                    )
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
        $offset = 0;
        $total = $this->getTotalLogCount($runUuid, $context);
        $run = $this->getMigrationRun($runUuid, $context);

        return function () use ($run, $runUuid, $offset, $total, $context): void {
            if ($run !== null) {
                \printf('%s%s', $this->getPrefixLogInformation($run), \PHP_EOL);
            }

            while ($offset < $total) {
                $logChunk = $this->getLogChunk($runUuid, $offset, $context);

                foreach ($logChunk->getElements() as $logEntry) {
                    \printf('[%s] %s%s', $logEntry->getLevel(), $logEntry->getCode(), \PHP_EOL);
                    \printf('%s%s', $logEntry->getTitle(), \PHP_EOL);
                    \printf('%s%s%s', $logEntry->getDescription(), \PHP_EOL, \PHP_EOL);
                }

                $offset += self::LOG_FETCH_LIMIT;
            }

            if ($run !== null) {
                \printf('%s%s%s', $this->getSuffixLogInformation($run), \PHP_EOL, \PHP_EOL);
            }
        };
    }

    public function clearDataOfRun(string $runUuid, Context $context): void
    {
        $run = $this->runRepo->search(new Criteria([$runUuid]), $context)->getEntities()->first();

        if ($run === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $runUuid);
        }

        if ($run->getStep()->isRunning()) {
            throw MigrationException::migrationIsAlreadyRunning();
        }

        $this->connection->executeStatement('DELETE FROM swag_migration_logging WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_data WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_media_file WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_run WHERE id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
    }

    public function isMediaProcessing(): bool
    {
        $unprocessedCount = $this->connection->executeQuery(
            'SELECT COUNT(id) FROM swag_migration_media_file WHERE processed = 0 and process_failure != 1'
        )->fetchOne();

        return $unprocessedCount !== '0';
    }

    private function extractBucketInformation(Bucket $bucket): array
    {
        /** @var TermsResult $titleResult */
        $titleResult = $bucket->getResult();
        $titleBucket = $titleResult->getBuckets()[0];

        /** @var TermsResult $entityResult */
        $entityResult = $titleBucket->getResult();
        $entityString = empty($entityResult->getBuckets()) ? '' : $entityResult->getBuckets()[0]->getKey();

        $levelString = '';
        if ($entityString !== '') {
            /** @var TermsResult $levelResult */
            $levelResult = $entityResult->getBuckets()[0]->getResult();
            $levelString = empty($levelResult->getBuckets()) ? '' : $levelResult->getBuckets()[0]->getKey();
        }

        return [
            'code' => $bucket->getKey(),
            'count' => $bucket->getCount(),
            'titleSnippet' => $titleBucket->getKey(),
            'entity' => $entityString,
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
        if ($connection !== null) {
            $connectionName = $connection->getName();
            $profileName = $connection->getProfileName();
            $gatewayName = $connection->getGatewayName();
        }

        $updatedAt = $run->getUpdatedAt();
        if ($updatedAt !== null) {
            $updatedAt = $updatedAt->format(self::LOG_TIME_FORMAT);
        } else {
            $updatedAt = '-';
        }

        $createdAt = $run->getCreatedAt();
        if ($createdAt !== null) {
            $createdAt = $createdAt->format(self::LOG_TIME_FORMAT);
        } else {
            $createdAt = '-';
        }

        return \sprintf(
            'Migration log generated at %s' . \PHP_EOL
            . 'Run id: %s' . \PHP_EOL
            . 'Status: %s' . \PHP_EOL
            . 'Created at: %s' . \PHP_EOL
            . 'Updated at: %s' . \PHP_EOL
            . 'Connection id: %s' . \PHP_EOL
            . 'Connection name: %s' . \PHP_EOL
            . 'Profile: %s' . \PHP_EOL
            . 'Gateway: %s' . \PHP_EOL . \PHP_EOL
            . 'Selected dataSets:' . \PHP_EOL . '%s' . \PHP_EOL
            . '--------------------Log-entries---------------------' . \PHP_EOL,
            \date(self::LOG_TIME_FORMAT),
            $run->getId(),
            $run->getStepValue(),
            $createdAt,
            $updatedAt,
            $run->getConnectionId() ?? '-',
            $connectionName,
            $profileName,
            $gatewayName,
            $this->getFormattedSelectedDataSets($run->getProgress())
        );
    }

    private function getFormattedSelectedDataSets(?MigrationProgress $progress): string
    {
        if ($progress === null || $progress->getDataSets()->count() < 1) {
            return '';
        }

        $output = '';
        foreach ($progress->getDataSets() as $dataSet) {
            $output .= \sprintf('- %s (total: %d)' . \PHP_EOL, $dataSet->getEntityName(), $dataSet->getTotal());
        }

        return $output;
    }

    private function getSuffixLogInformation(SwagMigrationRunEntity $run): string
    {
        $connection = $run->getConnection();
        $premapping = 'Associated connection not found';
        if ($connection !== null) {
            $premapping = $connection->getPremapping();
        }

        return \sprintf(
            '--------------------Additional-metadata---------------------' . \PHP_EOL
            . 'Environment information {JSON}:' . \PHP_EOL . '%s' . \PHP_EOL . \PHP_EOL
            . 'Premapping {JSON}: ----------------------------------------------------' . \PHP_EOL . '%s' . \PHP_EOL,
            \json_encode($run->getEnvironmentInformation(), \JSON_PRETTY_PRINT),
            \json_encode($premapping, \JSON_PRETTY_PRINT)
        );
    }
}
