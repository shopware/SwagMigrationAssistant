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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\Bucket;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
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

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        EntityRepositoryInterface $loggingRepo,
        EntityRepositoryInterface $runRepo,
        Connection $connection
    ) {
        $this->loggingRepo = $loggingRepo;
        $this->runRepo = $runRepo;
        $this->connection = $connection;
    }

    public function getGroupedLogsOfRun(
        string $runUuid,
        Context $context
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('level', LogEntryInterface::LOG_LEVEL_INFO),
                ]
            )
        );
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
                        'entity'
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
                /** @var SwagMigrationLoggingCollection $logChunk */
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
        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->runRepo->search(new Criteria([$runUuid]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException('run', $runUuid);
        }

        if ($run->getStatus() === SwagMigrationRunEntity::STATUS_RUNNING) {
            throw new MigrationIsRunningException();
        }

        $this->connection->executeStatement('DELETE FROM swag_migration_logging WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_data WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_media_file WHERE run_id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
        $this->connection->executeStatement('DELETE FROM swag_migration_run WHERE id = :runId', ['runId' => Uuid::fromHexToBytes($runUuid)]);
    }

    public function isMediaProcessing(): bool
    {
        $messageSize = $this->connection->executeQuery('SELECT size FROM message_queue_stats WHERE name = :name', ['name' => ProcessMediaMessage::class])->fetchColumn();

        return $messageSize > 0;
    }

    private function extractBucketInformation(Bucket $bucket): array
    {
        /** @var TermsResult $titleResult */
        $titleResult = $bucket->getResult();
        $titleBucket = $titleResult->getBuckets()[0];

        /** @var TermsResult $entityResult */
        $entityResult = $titleBucket->getResult();
        $entityString = empty($entityResult->getBuckets()) ? '' : $entityResult->getBuckets()[0]->getKey();

        return [
            'code' => $bucket->getKey(),
            'count' => $bucket->getCount(),
            'titleSnippet' => $titleBucket->getKey(),
            'entity' => $entityString,
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
        $run = $this->runRepo->search($criteria, $context)->getElements();
        if (!isset($run[$runUuid])) {
            return null;
        }

        return $run[$runUuid];
    }

    private function getLogChunk(string $runUuid, int $offset, Context $context): EntityCollection
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
            . 'Last control user id: %s' . \PHP_EOL
            . 'Connection id: %s' . \PHP_EOL
            . 'Connection name: %s' . \PHP_EOL
            . 'Profile: %s' . \PHP_EOL
            . 'Gateway: %s' . \PHP_EOL . \PHP_EOL
            . 'Selected data:' . \PHP_EOL . '%s' . \PHP_EOL
            . '--------------------Log-entries---------------------' . \PHP_EOL,
            \date(self::LOG_TIME_FORMAT),
            $run->getId(),
            $run->getStatus() ?? '-',
            $createdAt,
            $updatedAt,
            $run->getUserId() ?? '-',
            $run->getConnectionId() ?? '-',
            $connectionName,
            $profileName,
            $gatewayName,
            $this->getFormattedSelectedData($run->getProgress())
        );
    }

    private function getFormattedSelectedData(?array $progress): string
    {
        if ($progress === null || \count($progress) < 1) {
            return '';
        }

        $output = '';
        foreach ($progress as $group) {
            $output .= \sprintf('- %s (total: %d)' . \PHP_EOL, $group['id'], $group['total']);
            foreach ($group['entities'] as $entity) {
                $output .= \sprintf(
                    "\t- %s (total: %d)" . \PHP_EOL,
                    $entity['entityName'],
                    $entity['total']
                );
            }
        }

        return $output;
    }

    private function getSuffixLogInformation(SwagMigrationRunEntity $run): string
    {
        return \sprintf(
            '--------------------Additional-metadata---------------------' . \PHP_EOL
            . 'Environment information {JSON}:' . \PHP_EOL . '%s' . \PHP_EOL . \PHP_EOL
            . 'Premapping {JSON}: ----------------------------------------------------' . \PHP_EOL . '%s' . \PHP_EOL,
            \json_encode($run->getEnvironmentInformation(), \JSON_PRETTY_PRINT),
            \json_encode($run->getPremapping(), \JSON_PRETTY_PRINT)
        );
    }
}
