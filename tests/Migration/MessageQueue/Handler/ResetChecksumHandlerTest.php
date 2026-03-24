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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ResetChecksumHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
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

    private MigrationConfiguration $migrationConfiguration;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->migrationRunRepo = $this->createMock(EntityRepository::class);
        $this->runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $this->migrationConfiguration = new MigrationConfiguration();

        $this->handler = new ResetChecksumHandler(
            $this->connection,
            $this->messageBus,
            $this->migrationRunRepo,
            $this->runTransitionService,
            $this->migrationConfiguration,
        );
    }

    public function testInvokeWithNoMappingsToReset(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
        );

        $this->mockTotalCount(0);
        $this->mockResetChecksumsAndClearFlag(0);

        $this->migrationRunRepo
            ->expects($this->never())
            ->method('update');

        $this->migrationRunRepo
            ->expects($this->never())
            ->method('upsert');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSingleBatchWithoutRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
        );

        $this->mockTotalCount(2);
        $this->mockResetChecksumsAndClearFlag(2);

        $this->migrationRunRepo
            ->expects($this->never())
            ->method('update');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSingleBatchWithRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId
        );

        $this->mockTotalCount(1);
        $this->mockResetChecksumsAndClearFlag(1);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                return isset($data[0]['id'])
                    && $data[0]['id'] === $runId
                    && isset($data[0]['progress']);
            }));

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFullBatchDispatchesContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId
        );

        $this->mockTotalCount(500);
        $this->mockResetChecksumsOnly($this->migrationConfiguration->MIGRATION_DEFAULT_BATCH_SIZE);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $batchSize = $this->migrationConfiguration->MIGRATION_DEFAULT_BATCH_SIZE;

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) use ($connectionId, $batchSize) {
                return $dispatchedMessage instanceof ResetChecksumMessage
                    && $dispatchedMessage->getConnectionId() === $connectionId
                    && $dispatchedMessage->getProcessedMappings() === $batchSize
                    && $dispatchedMessage->getTotalMappings() === 500;
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeWithFullBatchResettingAllDispatchesContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
        );

        $this->mockTotalCount($this->migrationConfiguration->MIGRATION_DEFAULT_BATCH_SIZE);
        $this->mockResetChecksumsOnly($this->migrationConfiguration->MIGRATION_DEFAULT_BATCH_SIZE);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::isInstanceOf(ResetChecksumMessage::class))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

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
            $runId,
            DefaultEntities::PRODUCT,
            0,
            0,
            true
        );

        $this->mockResetChecksumsAndClearFlag(0);
        $this->mockRunSearch($runId);

        $this->runTransitionService
            ->expects($this->once())
            ->method('forceTransitionToRunStep')
            ->with($runId, MigrationStep::CLEANUP);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                $progress = $data[0]['progress'];

                return $data[0]['id'] === $runId
                    && \is_array($progress)
                    && isset($progress['isAborted'])
                    && $progress['isAborted'] === true;
            }));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::isInstanceOf(MigrationProcessMessage::class))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokePartOfAbortWithContinuation(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId,
            DefaultEntities::PRODUCT,
            null,
            0,
            true
        );

        $this->mockTotalCount(500);
        $this->mockResetChecksumsOnly($this->migrationConfiguration->MIGRATION_DEFAULT_BATCH_SIZE);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(static::callback(static function ($dispatchedMessage) use ($connectionId, $runId) {
                return $dispatchedMessage instanceof ResetChecksumMessage
                    && $dispatchedMessage->getConnectionId() === $connectionId
                    && $dispatchedMessage->getRunId() === $runId
                    && $dispatchedMessage->isPartOfAbort();
            }))
            ->willReturnCallback(static fn ($msg) => new Envelope($msg));

        $this->handler->__invoke($message);
    }

    public function testInvokeInitializesProgressOnFirstRun(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId
        );

        $this->mockTotalCount(100);
        $this->mockResetChecksumsAndClearFlag(2);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->handler->__invoke($message);
    }

    public function testInvokeContinuationWithTotalMappingsAlreadySet(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId,
            DefaultEntities::PRODUCT,
            100,
            50,
            false
        );

        $this->mockResetChecksumsAndClearFlag(2);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithSpecificEntity(): void
    {
        $connectionId = Uuid::randomHex();
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $entity = DefaultEntities::PRODUCT;

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
            $runId,
            $entity
        );

        $this->mockTotalCount(5);
        $this->mockResetChecksumsAndClearFlag(1);
        $this->mockRunSearch($runId);

        $this->migrationRunRepo
            ->expects($this->once())
            ->method('update')
            ->with(static::callback(static function ($data) use ($runId) {
                return $data[0]['id'] === $runId && isset($data[0]['progress']);
            }));

        $this->handler->__invoke($message);
    }

    public function testInvokeResettingAllWithoutRunId(): void
    {
        $connectionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $message = new ResetChecksumMessage(
            $connectionId,
            $context,
        );

        $this->mockTotalCount(2);
        $this->mockResetChecksumsAndClearFlag(2);

        $this->migrationRunRepo
            ->expects($this->never())
            ->method('update');

        $this->migrationRunRepo
            ->expects($this->never())
            ->method('upsert');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        $this->handler->__invoke($message);
    }

    private function mockTotalCount(int $count): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = static::createStub(Result::class);
        $result->method('fetchOne')->willReturn($count);

        $queryBuilder->method('executeQuery')->willReturn($result);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();

        $this->connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
    }

    private function mockResetChecksumsAndClearFlag(int $affectedRows): void
    {
        $this->connection
            ->method('executeStatement')
            ->willReturnCallback(static function (string $sql) use ($affectedRows): int {
                if (\str_contains($sql, 'swag_migration_mapping')) {
                    return $affectedRows;
                }

                if (\str_contains($sql, 'swag_migration_general_setting')) {
                    return 1;
                }

                return 0;
            });
    }

    private function mockResetChecksumsOnly(int $affectedRows): void
    {
        $this->connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with(static::stringContains('swag_migration_mapping'))
            ->willReturn($affectedRows);
    }

    private function mockRunSearch(string $runId): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId($runId);

        $collection = new SwagMigrationRunCollection([$run]);

        $searchResult = new EntitySearchResult(
            SwagMigrationRunDefinition::ENTITY_NAME,
            1,
            $collection,
            null,
            new Criteria(),
            Context::createDefaultContext(),
        );

        $this->migrationRunRepo
            ->method('search')
            ->willReturn($searchResult);
    }
}
