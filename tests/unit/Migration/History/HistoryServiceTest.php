<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace unit\Migration\History;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\History\HistoryService;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(HistoryService::class)]
class HistoryServiceTest extends TestCase
{
    public function testShouldThrowIfRunCantBeFound(): void
    {
        static::expectException(MigrationException::class);
        static::expectExceptionMessage('No SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity with UUID run-id found. Make sure the entity with the UUID exists.');

        $this->createHistoryService([], [])->downloadLogsOfRun(
            'run-id',
            Context::createDefaultContext()
        );
    }

    /**
     * @param SwagMigrationLoggingEntity[] $logs
     */
    #[DataProvider('logCountProvider')]
    public function testShouldDownloadLogs(SwagMigrationRunEntity $run, SwagMigrationConnectionEntity $connection, array $logs): void
    {
        $callback = $this->createHistoryService(
            $logs,
            [$run],
        )->downloadLogsOfRun(
            $run->getId(),
            Context::createDefaultContext()
        );

        $output = $this->assertCallback($callback);

        $this->assertOutput(
            $output,
            $run,
            $connection,
            $logs
        );
    }

    /**
     * @return \Generator<string, array{0: SwagMigrationRunEntity, 1: SwagMigrationConnectionEntity, 2: SwagMigrationLoggingEntity[]}>
     */
    public static function logCountProvider(): \Generator
    {
        $ids = new IdsCollection();

        $migrationLog = new SwagMigrationLoggingEntity();
        $migrationLog->setId($ids->get('log1'));
        $migrationLog->setAutoIncrement(1);
        $migrationLog->setLevel(AbstractMigrationLogEntry::LOG_LEVEL_WARNING);
        $migrationLog->setCode('TEST_CODE');
        $migrationLog->setProfileName('profile name');
        $migrationLog->setGatewayName('gateway name');
        $migrationLog->setCreatedAt(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $migrationLog->setEntityName('entity');
        $migrationLog->setFieldName('field name');
        $migrationLog->setFieldSourcePath('field source path');
        $migrationLog->setExceptionMessage('exception message');
        $migrationLog->setSourceData([['source' => 'data']]);
        $migrationLog->setConvertedData([['converted' => 'data']]);
        $migrationLog->setExceptionTrace([['exception' => 'trace']]);
        $migrationLog->setEntityId($ids->get('entityId_log1'));

        $premapping = new PremappingStruct(
            'entity',
            [new PremappingEntityStruct('test', 'test', 'test')],
            [new PremappingChoiceStruct('test', 'test')]
        );

        $migrationConnection = new SwagMigrationConnectionEntity();
        $migrationConnection->setId($ids->get('connection'));
        $migrationConnection->setName('connection name');
        $migrationConnection->setProfileName('profile name');
        $migrationConnection->setGatewayName('gateway name');
        $migrationConnection->setPremapping([$premapping]);

        $migrationRun = new SwagMigrationRunEntity();
        $migrationRun->setId($ids->get('run'));
        $migrationRun->setStep(MigrationStep::FINISHED);
        $migrationRun->setCreatedAt(new \DateTimeImmutable('2024-01-01 12:00:00'));
        $migrationRun->setUpdatedAt(new \DateTimeImmutable('2024-01-01 13:00:00'));
        $migrationRun->setEnvironmentInformation(['php' => '8.2']);
        $migrationRun->setConnection($migrationConnection);
        $migrationRun->setConnectionId($migrationConnection->getId());

        yield 'full' => [
            $migrationRun,
            $migrationConnection,
            [$migrationLog],
        ];

        yield 'no logs' => [
            $migrationRun,
            $migrationConnection,
            [],
        ];

        $migrationLog2 = clone $migrationLog;
        $migrationLog2->setId($ids->get('log2'));
        $migrationLog2->setAutoIncrement(2);
        $migrationLog2->setLevel(AbstractMigrationLogEntry::LOG_LEVEL_ERROR);
        $migrationLog2->setCode('TEST_CODE_2');

        yield 'multiple logs' => [
            $migrationRun,
            $migrationConnection,
            [$migrationLog, $migrationLog2],
        ];

        $minimalRun = new SwagMigrationRunEntity();
        $minimalRun->setId($ids->get('run-minimal'));
        $minimalRun->setStep(MigrationStep::ABORTED);

        yield 'minimal prefix log information' => [
            $minimalRun,
            new SwagMigrationConnectionEntity(),
            [],
        ];

        $minimalLog = new SwagMigrationLoggingEntity();
        $minimalLog->setId($ids->get('log-minimal'));
        $minimalLog->setAutoIncrement(1);
        $minimalLog->setLevel(AbstractMigrationLogEntry::LOG_LEVEL_INFO);
        $minimalLog->setCode('TEST_CODE_MINIMAL');
        $minimalLog->setProfileName('profile name');
        $minimalLog->setGatewayName('gateway name');
        $minimalLog->setEntityId($ids->get('entityId_log-minimal'));

        yield 'minimal log information' => [
            $migrationRun,
            $migrationConnection,
            [$minimalLog],
        ];
    }

    /**
     * @param SwagMigrationLoggingEntity[] $logs
     */
    private function assertOutput(
        string $output,
        SwagMigrationRunEntity $run,
        SwagMigrationConnectionEntity $connection,
        array $logs,
    ): void {
        static::assertStringContainsString('########## MIGRATION LOG ##########', $output);
        static::assertStringContainsString('########## RUN INFORMATION ##########', $output);
        static::assertStringContainsString('########## CONNECTION INFORMATION ##########', $output);
        static::assertStringContainsString('########## SELECTED DATASETS ##########', $output);
        static::assertStringContainsString('########## ADDITIONAL METADATA ##########', $output);
        static::assertStringContainsString('########## LOG ENTRIES ##########', $output);

        static::assertStringContainsString('Generated at: ', $output);
        static::assertStringContainsString('Run ID: ' . $run->getId(), $output);
        static::assertStringContainsString('Status: ' . $run->getStepValue(), $output);
        static::assertStringContainsString('Created at: ' . ($run->getCreatedAt()?->format(HistoryService::LOG_TIME_FORMAT) ?? '-'), $output);
        static::assertStringContainsString('Updated at: ' . ($run->getUpdatedAt()?->format(HistoryService::LOG_TIME_FORMAT) ?? '-'), $output);
        static::assertStringContainsString('Connection ID: ' . ($run->getConnectionId() ?? '-'), $output);
        static::assertStringContainsString('Connection name: ' . ($run->getConnection()?->getName() ?? '-'), $output);
        static::assertStringContainsString('Profile name: ' . ($run->getConnection()?->getProfileName() ?? '-'), $output);
        static::assertStringContainsString('Gateway name: ' . ($run->getConnection()?->getGatewayName() ?? '-'), $output);

        if (!empty($run->getEnvironmentInformation())) {
            $env = \json_encode($run->getEnvironmentInformation(), \JSON_PRETTY_PRINT);

            static::assertNotFalse($env);
            static::assertStringContainsString('Environment information (JSON):', $output);
            static::assertStringContainsString($env, $output);
        }

        if (!empty($connection->getPremapping())) {
            $premapping = \json_encode($connection->getPremapping(), \JSON_PRETTY_PRINT);

            static::assertNotFalse($premapping);
            static::assertStringContainsString('Pre-mapping (JSON):', $output);
            static::assertStringContainsString($premapping, $output);
        } else {
            static::assertStringContainsString('Associated connection not found', $output);
        }

        if (empty($logs)) {
            static::assertStringContainsString('No log entries found for this migration run.', $output);

            return;
        }

        foreach ($logs as $index => $log) {
            static::assertInstanceOf(SwagMigrationLoggingEntity::class, $log);

            static::assertStringContainsString('----- Log Entry #' . ($index + 1) . ' -----', $output);
            static::assertStringContainsString('ID: ' . $log->getId(), $output);
            static::assertStringContainsString('Level: ' . $log->getLevel(), $output);
            static::assertStringContainsString('Code: ' . $log->getCode(), $output);
            static::assertStringContainsString('Profile name: ' . $log->getProfileName(), $output);
            static::assertStringContainsString('Gateway name: ' . $log->getGatewayName(), $output);
            static::assertStringContainsString('Created at: ' . ($log->getCreatedAt()?->format(HistoryService::LOG_TIME_FORMAT) ?? '-'), $output);

            if (!empty($log->getEntityName())) {
                static::assertStringContainsString('Entity: ' . $log->getEntityName(), $output);
            }

            if (!empty($log->getFieldName())) {
                static::assertStringContainsString('Field: ' . $log->getFieldName(), $output);
            }

            if (!empty($log->getFieldSourcePath())) {
                static::assertStringContainsString('Source path: ' . $log->getFieldSourcePath(), $output);
            }

            if (!empty($log->getExceptionMessage())) {
                static::assertStringContainsString('Exception message: ' . $log->getExceptionMessage(), $output);
            }

            if (!empty($log->getSourceData())) {
                $sourceData = \json_encode($log->getSourceData(), \JSON_PRETTY_PRINT);
                static::assertNotFalse($sourceData);
                static::assertStringContainsString('Source data (JSON):', $output);
                static::assertStringContainsString($sourceData, $output);
            }

            if (!empty($log->getConvertedData())) {
                $convertedData = \json_encode($log->getConvertedData(), \JSON_PRETTY_PRINT);
                static::assertNotFalse($convertedData);
                static::assertStringContainsString('Converted data (JSON):', $output);
                static::assertStringContainsString($convertedData, $output);
            }

            if (!empty($log->getExceptionTrace())) {
                $exceptionTrace = \json_encode($log->getExceptionTrace(), \JSON_PRETTY_PRINT);
                static::assertNotFalse($exceptionTrace);
                static::assertStringContainsString('Exception trace (JSON):', $output);
                static::assertStringContainsString($exceptionTrace, $output);
            }
        }
    }

    private function assertCallback(\Closure $callback): string
    {
        static::assertIsCallable($callback);

        ob_start();
        $callback();
        $output = ob_get_clean();

        static::assertIsString($output);

        return $output;
    }

    /**
     * @param SwagMigrationLoggingEntity[] $logs
     * @param SwagMigrationRunEntity[] $runs
     */
    private function createHistoryService(array $logs, array $runs): HistoryService
    {
        /**
         * @var StaticEntityRepository<SwagMigrationLoggingCollection> $loggingRepo
         */
        $loggingRepo = $this->createMockAggregateRepo(
            $logs,
            new SwagMigrationLoggingDefinition(),
            SwagMigrationLoggingCollection::class
        );

        /**
         * @var StaticEntityRepository<SwagMigrationRunCollection> $runRepo
         */
        $runRepo = $this->createMockAggregateRepo(
            $runs,
            new SwagMigrationRunDefinition(),
            SwagMigrationRunCollection::class
        );

        return new HistoryService(
            $loggingRepo,
            $runRepo,
        );
    }

    /**
     * @template TEntityCollection of EntityCollection
     *
     * @param Entity[] $entities
     * @param class-string<TEntityCollection> $collectionClass
     *
     * @return StaticEntityRepository<TEntityCollection>
     */
    private function createMockAggregateRepo(
        array $entities,
        EntityDefinition $definition,
        string $collectionClass,
    ): StaticEntityRepository {
        static::assertTrue(\class_exists($collectionClass));

        /** @var StaticEntityRepository<TEntityCollection>&MockObject $repo */
        $repo = $this->getMockBuilder(StaticEntityRepository::class)
            ->setConstructorArgs([[$entities], $definition])
            ->onlyMethods(['aggregate'])
            ->getMock();

        $collection = new AggregationResultCollection();
        $collection->set('count', new CountResult('count', \count($entities)));

        $repo->method('aggregate')->willReturn($collection);

        return $repo;
    }
}
