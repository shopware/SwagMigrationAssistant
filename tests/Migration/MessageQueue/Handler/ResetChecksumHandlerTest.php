<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ResetChecksumHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('fundamentals@after-sales')]
#[CoversClass(ResetChecksumHandler::class)]
class ResetChecksumHandlerTest extends TestCase
{
    private MockObject&Connection $connection;

    private MockObject&MessageBusInterface $messageBus;

    /**
     * @var MockObject&EntityRepository<SwagMigrationRunCollection>
     */
    private MockObject&EntityRepository $migrationRunRepo;

    private MockObject&RunTransitionServiceInterface $runTransitionService;

    private ResetChecksumHandler $handler;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->migrationRunRepo = $this->createMock(EntityRepository::class);
        $this->runTransitionService = $this->createMock(RunTransitionServiceInterface::class);

        $this->handler = new ResetChecksumHandler(
            $this->connection,
            $this->messageBus,
            $this->migrationRunRepo,
            $this->runTransitionService
        );
    }

    public function testInvokeWithNoMappingsToReset(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false
        );

        $this->mockQueryBuilders(
            [],
            0
        );

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->migrationRunRepo
            ->expects(static::never())
            ->method('update');

        $this->migrationRunRepo
            ->expects(static::never())
            ->method('upsert');

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSingleBatchWithoutRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = [Uuid::randomBytes(), Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false
        );

        $this->mockQueryBuilders(
            $batchIds,
            2
        );

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->migrationRunRepo
            ->expects(static::never())
            ->method('update');

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSingleBatchWithRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = [Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId
        );

        $this->mockQueryBuilders(
            $batchIds,
            1
        );

        // expect progress update twice: once for initialization, once for progress update
        $this->migrationRunRepo
            ->expects(static::exactly(2))
            ->method('update')
            ->with(static::callback(function ($data) use ($runId) {
                return isset($data[0]['id'])
                    && $data[0]['id'] === $runId
                    && isset($data[0]['progress']);
            }));

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFullBatchDispatchesContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = \array_fill(
            0,
            ResetChecksumHandler::BATCH_SIZE,
            Uuid::randomBytes()
        );

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId
        );

        $this->mockQueryBuilders(
            $batchIds,
            500
        );

        $this->migrationRunRepo
            ->expects(static::exactly(2))
            ->method('update')
            ->with(static::callback(function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) use ($connectionId) {
                return $dispatchedMessage instanceof ResetChecksumMessage
                    && $dispatchedMessage->getConnectionId() === $connectionId
                    && $dispatchedMessage->getProcessedMappings() === ResetChecksumHandler::BATCH_SIZE
                    && $dispatchedMessage->getTotalMappings() === 500;
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->connection
            ->expects(static::never())
            ->method('executeStatement');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFullBatchResettingAllDispatchesContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = \array_fill(
            0,
            ResetChecksumHandler::BATCH_SIZE,
            Uuid::randomBytes()
        );

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            true
        );

        $this->mockQueryBuilders(
            $batchIds,
            ResetChecksumHandler::BATCH_SIZE
        );

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(ResetChecksumMessage::class))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->connection
            ->expects(static::never())
            ->method('executeStatement');

        $this->handler->__invoke($message);
    }

    public function testInvokePartOfAbortWithCompletion(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId,
            DefaultEntities::PRODUCT,
            0,
            0,
            true
        );

        $this->mockQueryBuilders(
            [],
            0
        );

        $this->runTransitionService
            ->expects(static::once())
            ->method('forceTransitionToRunStep')
            ->with($runId, MigrationStep::CLEANUP);

        $this->migrationRunRepo
            ->expects(static::once())
            ->method('upsert')
            ->with(static::callback(function ($data) use ($runId) {
                $progress = $data[0]['progress'];

                return $data[0]['id'] === $runId
                    && $progress instanceof MigrationProgress
                    && $progress->isAborted();
            }));

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(MigrationProcessMessage::class))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->handler->__invoke($message);
    }

    public function testInvokePartOfAbortWithContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = \array_fill(
            0,
            ResetChecksumHandler::BATCH_SIZE,
            Uuid::randomBytes()
        );

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId,
            DefaultEntities::PRODUCT,
            null,
            0,
            true
        );

        $this->mockQueryBuilders(
            $batchIds,
            500
        );

        $this->migrationRunRepo
            ->expects(static::exactly(2))
            ->method('update');

        $this->messageBus
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function ($dispatchedMessage) use ($connectionId, $runId) {
                return $dispatchedMessage instanceof ResetChecksumMessage
                    && $dispatchedMessage->getConnectionId() === $connectionId
                    && $dispatchedMessage->getRunId() === $runId
                    && $dispatchedMessage->isPartOfAbort();
            }))
            ->willReturnCallback(fn ($msg) => new Envelope($msg));

        $this->connection
            ->expects(static::never())
            ->method('executeStatement');

        $this->handler->__invoke($message);
    }

    public function testInvokeInitializesProgressOnFirstRun(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = [Uuid::randomBytes(), Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId
        );

        $this->mockQueryBuilders(
            $batchIds,
            100
        );

        $this->migrationRunRepo
            ->expects(static::exactly(2))
            ->method('update')
            ->with(static::callback(function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->handler->__invoke($message);
    }

    public function testInvokeContinuationWithTotalMappingsAlreadySet(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = [Uuid::randomBytes(), Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId,
            DefaultEntities::PRODUCT,
            100,
            50,
            false
        );

        $this->mockQueryBuildersForContinuation(
            $batchIds
        );

        $this->migrationRunRepo
            ->expects(static::once())
            ->method('update')
            ->with(static::callback(function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSpecificEntity(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $entity = DefaultEntities::PRODUCT;
        $batchIds = [Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            false,
            $runId,
            $entity
        );

        $this->mockQueryBuilders(
            $batchIds,
            5
        );

        $this->migrationRunRepo
            ->expects(static::exactly(2))
            ->method('update')
            ->with(static::callback(function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->handler->__invoke($message);
    }

    public function testInvokeResettingAllWithoutRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $batchIds = [Uuid::randomBytes(), Uuid::randomBytes()];

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            true
        );

        $this->mockQueryBuilders(
            $batchIds,
            2
        );

        $this->migrationRunRepo
            ->expects(static::never())
            ->method('update');

        $this->migrationRunRepo
            ->expects(static::never())
            ->method('upsert');

        $this->connection
            ->expects(static::once())
            ->method('executeStatement')
            ->with(static::stringContains('UPDATE swag_migration_general_setting'));

        $this->messageBus
            ->expects(static::never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    /**
     * @param list<string> $batchIds
     */
    private function mockQueryBuilders(array $batchIds, int $totalCount): void
    {
        $countQueryBuilder = $this->createMock(QueryBuilder::class);
        $countResult = static::createStub(Result::class);
        $countResult->method('fetchOne')->willReturn($totalCount);

        $countQueryBuilder->method('executeQuery')->willReturn($countResult);
        $countQueryBuilder->method('select')->willReturnSelf();
        $countQueryBuilder->method('from')->willReturnSelf();
        $countQueryBuilder->method('where')->willReturnSelf();
        $countQueryBuilder->method('andWhere')->willReturnSelf();
        $countQueryBuilder->method('setParameter')->willReturnSelf();
        $countQueryBuilder->method('innerJoin')->willReturnSelf();

        $selectQueryBuilder = $this->createMock(QueryBuilder::class);
        $selectResult = static::createStub(Result::class);
        $selectResult->method('fetchFirstColumn')->willReturn($batchIds);

        $selectQueryBuilder->method('executeQuery')->willReturn($selectResult);
        $selectQueryBuilder->method('select')->willReturnSelf();
        $selectQueryBuilder->method('from')->willReturnSelf();
        $selectQueryBuilder->method('where')->willReturnSelf();
        $selectQueryBuilder->method('andWhere')->willReturnSelf();
        $selectQueryBuilder->method('setParameter')->willReturnSelf();
        $selectQueryBuilder->method('setMaxResults')->willReturnSelf();
        $selectQueryBuilder->method('innerJoin')->willReturnSelf();

        $updateQueryBuilder = $this->createMock(QueryBuilder::class);
        $updateQueryBuilder->method('update')->willReturnSelf();
        $updateQueryBuilder->method('set')->willReturnSelf();
        $updateQueryBuilder->method('where')->willReturnSelf();
        $updateQueryBuilder->method('setParameter')->willReturnSelf();
        $updateQueryBuilder->method('executeStatement')->willReturn(\count($batchIds));

        $this->connection
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $countQueryBuilder,
                $selectQueryBuilder,
                $updateQueryBuilder
            );
    }

    /**
     * @param list<string> $batchIds
     */
    private function mockQueryBuildersForContinuation(array $batchIds): void
    {
        $selectQueryBuilder = $this->createMock(QueryBuilder::class);
        $selectResult = static::createStub(Result::class);
        $selectResult->method('fetchFirstColumn')->willReturn($batchIds);

        $selectQueryBuilder->method('executeQuery')->willReturn($selectResult);
        $selectQueryBuilder->method('select')->willReturnSelf();
        $selectQueryBuilder->method('from')->willReturnSelf();
        $selectQueryBuilder->method('where')->willReturnSelf();
        $selectQueryBuilder->method('andWhere')->willReturnSelf();
        $selectQueryBuilder->method('setParameter')->willReturnSelf();
        $selectQueryBuilder->method('setMaxResults')->willReturnSelf();
        $selectQueryBuilder->method('innerJoin')->willReturnSelf();

        $updateQueryBuilder = $this->createMock(QueryBuilder::class);
        $updateQueryBuilder->method('update')->willReturnSelf();
        $updateQueryBuilder->method('set')->willReturnSelf();
        $updateQueryBuilder->method('where')->willReturnSelf();
        $updateQueryBuilder->method('setParameter')->willReturnSelf();
        $updateQueryBuilder->method('executeStatement')->willReturn(\count($batchIds));

        $this->connection
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $selectQueryBuilder,
                $updateQueryBuilder
            );
    }
}
