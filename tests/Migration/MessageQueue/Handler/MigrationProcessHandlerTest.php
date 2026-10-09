<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\MediaFileMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessorRegistry;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MigrationProcessorInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('fundamentals@after-sales')]
class MigrationProcessHandlerTest extends TestCase
{
    private MigrationProcessHandler $migrationProcessHandler;

    protected function setUp(): void
    {
        $this->migrationProcessHandler = new MigrationProcessHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class),
            new MigrationConfiguration(),
            $this->createMock(LoggingServiceInterface::class),
        );
    }

    public function testInvokeWithoutRun(): void
    {
        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::RUN_NOT_FOUND, $exception->getErrorCode());
        }
    }

    public function testInvokeWithoutRunProgress(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class),
            new MigrationConfiguration(),
            $this->createMock(LoggingServiceInterface::class),
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::NO_RUN_PROGRESS_FOUND, $exception->getErrorCode());
        }
    }

    public function testInvokeWithoutMigrationContext(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress(new MigrationProgress(0, 100, new ProgressDataSetCollection(), 'product', 0));

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class),
            new MigrationConfiguration(),
            $this->createMock(LoggingServiceInterface::class),
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::MIGRATION_CONTEXT_NOT_CREATED, $exception->getErrorCode());
        }
    }

    public function testInvoke(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress(new MigrationProgress(0, 100, new ProgressDataSetCollection(), 'product', 0));
        $run->setStep(MigrationStep::FETCHING);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $processorRegistry = $this->createMock(MigrationProcessorRegistry::class);
        $processorRegistry
            ->expects($this->once())
            ->method('getProcessor')
            ->willReturn($this->createMock(MigrationProcessorInterface::class));

        $migrationContextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $migrationContextFactory->method('create')->willReturn(new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile()
        ));

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $migrationContextFactory,
            $processorRegistry,
            new MigrationConfiguration(),
            $this->createMock(LoggingServiceInterface::class),
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        $this->migrationProcessHandler->__invoke($message);
    }

    public function testInvokeFlushesLogsOfTheProcessedStep(): void
    {
        $writtenLogs = 0;
        $loggingRepo = $this->createMock(EntityRepository::class);
        $loggingRepo->method('create')->willReturnCallback(
            static function (array $logs) use (&$writtenLogs): EntityWrittenContainerEvent {
                $writtenLogs += \count($logs);

                return new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []);
            }
        );
        $loggingService = new LoggingService($loggingRepo, new NullLogger(), new MigrationConfiguration());

        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress(new MigrationProgress(0, 100, new ProgressDataSetCollection(), 'media', 0));
        $run->setStep(MigrationStep::MEDIA_PROCESSING);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $processor = $this->createMock(MigrationProcessorInterface::class);
        $processor->method('process')->willReturnCallback(
            static function () use ($loggingService, $run): void {
                $loggingService->log(
                    (new MigrationLogBuilder($run->getId(), Shopware55Profile::PROFILE_NAME, 'local'))
                        ->build(MediaFileMissingLog::class)
                );
            }
        );

        $processorRegistry = $this->createMock(MigrationProcessorRegistry::class);
        $processorRegistry->method('getProcessor')->willReturn($processor);

        $migrationContextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $migrationContextFactory->method('create')->willReturn(new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware55Profile()
        ));

        $handler = new MigrationProcessHandler(
            $repository,
            $migrationContextFactory,
            $processorRegistry,
            new MigrationConfiguration(),
            $loggingService,
        );

        $handler->__invoke(new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex()));

        static::assertSame(1, $writtenLogs);
    }
}
