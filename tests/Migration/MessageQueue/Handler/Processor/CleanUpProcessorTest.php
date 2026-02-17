<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler\Processor;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\CleanUpProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('fundamentals@after-sales')]
class CleanUpProcessorTest extends TestCase
{
    private CleanUpProcessor $processor;

    private CollectingMessageBus $bus;

    private MockObject&Connection $dbalConnection;

    /**
     * @var MockObject&EntityRepository<SwagMigrationRunCollection>
     */
    private MockObject&EntityRepository $migrationRunRepo;

    private MockObject&RunTransitionServiceInterface $runTransitionService;

    protected function setUp(): void
    {
        $this->dbalConnection = $this->createMock(Connection::class);
        $this->migrationRunRepo = $this->createMock(EntityRepository::class);
        $this->runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $this->bus = new CollectingMessageBus();
        $this->processor = new CleanUpProcessor(
            $this->migrationRunRepo,
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->runTransitionService,
            $this->dbalConnection,
            $this->bus
        );
    }

    public function testSupports(): void
    {
        static::assertTrue($this->processor->supports(MigrationStep::CLEANUP));
        static::assertFalse($this->processor->supports(MigrationStep::FETCHING));
        static::assertFalse($this->processor->supports(MigrationStep::WRITING));
    }

    public function testProcessingInitializesTotal(): void
    {
        $progress = new MigrationProgress(
            0,
            0,
            new ProgressDataSetCollection(),
            'product',
            0
        );

        $run = new SwagMigrationRunEntity();
        $runId = Uuid::randomHex();
        $run->setId($runId);
        $run->setProgress($progress);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            null,
            $runId,
        );

        $countResult = $this->createMock(Result::class);
        $countResult->method('fetchOne')->willReturn(500);

        $selectResult = $this->createMock(Result::class);
        $selectResult->method('fetchFirstColumn')->willReturn([]);

        $countQueryBuilder = $this->createMock(QueryBuilder::class);
        $countQueryBuilder->method('select')->willReturnSelf();
        $countQueryBuilder->method('from')->willReturnSelf();
        $countQueryBuilder->method('executeQuery')->willReturn($countResult);

        $selectQueryBuilder = $this->createMock(QueryBuilder::class);
        $selectQueryBuilder->method('select')->willReturnSelf();
        $selectQueryBuilder->method('from')->willReturnSelf();
        $selectQueryBuilder->method('setMaxResults')->willReturnSelf();
        $selectQueryBuilder->method('executeQuery')->willReturn($selectResult);

        $this->dbalConnection->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $countQueryBuilder,
            $selectQueryBuilder
        );

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertSame(500, $progress->getTotal());
        static::assertSame(0, $progress->getProgress());
        static::assertCount(1, $this->bus->getMessages());
    }

    public function testProcessingDeletesBatchAndUpdatesProgress(): void
    {
        $progress = new MigrationProgress(100, 50, new ProgressDataSetCollection(), 'product', 0);

        $run = new SwagMigrationRunEntity();
        $runId = Uuid::randomHex();
        $run->setId($runId);
        $run->setProgress($progress);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            null,
            $runId,
        );

        $selectResult = $this->createMock(Result::class);
        $selectResult->method('fetchFirstColumn')->willReturn(
            \array_fill(0, 10, Uuid::randomBytes())
        );

        $selectQueryBuilder = $this->createMock(QueryBuilder::class);
        $selectQueryBuilder->method('select')->willReturnSelf();
        $selectQueryBuilder->method('from')->willReturnSelf();
        $selectQueryBuilder->method('setMaxResults')->willReturnSelf();
        $selectQueryBuilder->method('executeQuery')->willReturn($selectResult);

        $deleteQueryBuilder = $this->createMock(QueryBuilder::class);
        $deleteQueryBuilder->method('delete')->willReturnSelf();
        $deleteQueryBuilder->method('where')->willReturnSelf();
        $deleteQueryBuilder->method('setParameter')->with('ids', static::anything(), ArrayParameterType::BINARY)->willReturnSelf();
        $deleteQueryBuilder->method('executeStatement')->willReturn(10);

        $this->dbalConnection->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $selectQueryBuilder,
            $deleteQueryBuilder
        );

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertSame(100, $progress->getProgress());
        static::assertCount(1, $this->bus->getMessages());
        static::assertInstanceOf(MigrationProcessMessage::class, $this->bus->getMessages()[0]->getMessage());
    }

    public function testProcessingTransitionsToIndexingWhenComplete(): void
    {
        $progress = new MigrationProgress(100, 90, new ProgressDataSetCollection(), 'product', 0);

        $run = new SwagMigrationRunEntity();
        $runId = Uuid::randomHex();
        $run->setId($runId);
        $run->setProgress($progress);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            null,
            $runId,
        );

        $selectResult = $this->createMock(Result::class);
        $selectResult->method('fetchFirstColumn')->willReturn([]);

        $selectQueryBuilder = $this->createMock(QueryBuilder::class);
        $selectQueryBuilder->method('select')->willReturnSelf();
        $selectQueryBuilder->method('from')->willReturnSelf();
        $selectQueryBuilder->method('setMaxResults')->willReturnSelf();
        $selectQueryBuilder->method('executeQuery')->willReturn($selectResult);

        $this->dbalConnection->method('createQueryBuilder')->willReturn($selectQueryBuilder);

        $this->runTransitionService
            ->expects($this->once())
            ->method('transitionToRunStep')
            ->with($runId, MigrationStep::INDEXING);

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }
}
