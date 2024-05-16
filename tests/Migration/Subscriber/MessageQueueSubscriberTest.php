<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Subscriber;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationProgressStatus;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Subscriber\MessageQueueSubscriber;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

class MessageQueueSubscriberTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    /**
     * @param array{messageCount: int, exceptionCount: int, logCount: int, updateCount: int, step: string} $expected
     */
    #[DataProvider('getOnWorkerMessageFailedProvider')]
    public function testOnWorkerMessageFailed(string $progressStatus, string $runStatus, bool $messageWillRetry, array $expected): void
    {
        $migrationProgress = new MigrationProgress(MigrationProgressStatus::from($progressStatus), 0, 1, new ProgressDataSetCollection(), ProductDefinition::ENTITY_NAME, 0);

        $runUuid = Uuid::randomHex();
        $run = new SwagMigrationRunEntity();
        $run->setStatus($runStatus);
        $run->setId($runUuid);
        $run->setProgress($migrationProgress);

        /** @var StaticEntityRepository<SwagMigrationRunCollection> $repository */
        $repository = new StaticEntityRepository(
            [new EntitySearchResult(SwagMigrationRunDefinition::ENTITY_NAME, 1, new EntityCollection([$run]), null, new Criteria(), $this->context)],
            new SwagMigrationRunDefinition()
        );

        $dummyLoggingService = new DummyLoggingService();
        $busMock = new CollectingMessageBus();
        $event = new WorkerMessageFailedEvent(new Envelope(new MigrationProcessMessage($this->context, $runUuid)), '', new \Exception());

        if ($messageWillRetry) {
            $event->setForRetry();
        }

        $subscriber = new MessageQueueSubscriber(
            $busMock,
            $repository,
            $dummyLoggingService
        );

        $subscriber->onWorkerMessageFailed($event);

        static::assertCount($expected['messageCount'], $busMock->getMessages());
        static::assertSame($expected['exceptionCount'], $migrationProgress->getExceptionCount());
        static::assertCount($expected['logCount'], $dummyLoggingService->getLoggingArray());
        static::assertCount($expected['updateCount'], $repository->updates);
        static::assertSame($expected['step'], $migrationProgress->getStepValue());

        if ($expected['updateCount'] === 0) {
            return;
        }

        static::assertSame($migrationProgress, $repository->updates[0][0]['progress']);
    }

    /**
     * @return \Generator<string, array{progressStatus: string, runStatus: string, messageWillRetry: bool, expected: array{messageCount: int, exceptionCount: int, logCount: int, updateCount: int, step: string}}>
     */
    public static function getOnWorkerMessageFailedProvider(): \Generator
    {
        yield 'Aborting migration should be aborted' => [
            'progressStatus' => MigrationProgressStatus::ABORTING->value,
            'runStatus' => SwagMigrationRunEntity::STATUS_ABORTED,
            'messageWillRetry' => false,
            'expected' => [
                'messageCount' => 0,
                'exceptionCount' => 1,
                'logCount' => 2,
                'updateCount' => 1,
                'step' => MigrationProgressStatus::ABORTED->value,
            ],
        ];

        yield 'Migration in fetching state and message will be retry should raise only exception count' => [
            'progressStatus' => MigrationProgressStatus::FETCHING->value,
            'runStatus' => SwagMigrationRunEntity::STATUS_RUNNING,
            'messageWillRetry' => true,
            'expected' => [
                'messageCount' => 0,
                'exceptionCount' => 1,
                'logCount' => 1,
                'updateCount' => 1,
                'step' => MigrationProgressStatus::FETCHING->value,
            ],
        ];

        yield 'Migration in fetching state and message will NOT be retry should raise exception count and add new message to bus' => [
            'progressStatus' => MigrationProgressStatus::FETCHING->value,
            'runStatus' => SwagMigrationRunEntity::STATUS_RUNNING,
            'messageWillRetry' => false,
            'expected' => [
                'messageCount' => 1,
                'exceptionCount' => 1,
                'logCount' => 1,
                'updateCount' => 1,
                'step' => MigrationProgressStatus::FETCHING->value,
            ],
        ];

        yield 'Migration in aborted state should do nothing' => [
            'progressStatus' => MigrationProgressStatus::ABORTED->value,
            'runStatus' => SwagMigrationRunEntity::STATUS_ABORTED,
            'messageWillRetry' => false,
            'expected' => [
                'messageCount' => 0,
                'exceptionCount' => 0,
                'logCount' => 0,
                'updateCount' => 0,
                'step' => MigrationProgressStatus::ABORTED->value,
            ],
        ];
    }

    public function testOnWorkerMessageFailedShouldRaiseExceptionCountAndAbortRunAndAddNewMessageToBus(): void
    {
        $migrationProgress = new MigrationProgress(
            MigrationProgressStatus::FETCHING,
            0,
            1,
            new ProgressDataSetCollection(),
            ProductDefinition::ENTITY_NAME,
            0,
            3
        );

        $runUuid = Uuid::randomHex();
        $run = new SwagMigrationRunEntity();
        $run->setStatus(SwagMigrationRunEntity::STATUS_RUNNING);
        $run->setId($runUuid);
        $run->setProgress($migrationProgress);

        /** @var StaticEntityRepository<SwagMigrationRunCollection> $repository */
        $repository = new StaticEntityRepository(
            [
                new EntitySearchResult(
                    SwagMigrationRunDefinition::ENTITY_NAME,
                    1,
                    new EntityCollection([$run]),
                    null,
                    new Criteria(),
                    $this->context
                ),
            ],
            new SwagMigrationRunDefinition()
        );

        $dummyLoggingService = new DummyLoggingService();
        $busMock = new CollectingMessageBus();
        $event = new WorkerMessageFailedEvent(
            new Envelope(new MigrationProcessMessage($this->context, $runUuid)),
            '',
            new \Exception()
        );
        $subscriber = new MessageQueueSubscriber(
            $busMock,
            $repository,
            $dummyLoggingService
        );

        $subscriber->onWorkerMessageFailed($event);

        static::assertCount(1, $busMock->getMessages());
        static::assertSame(4, $migrationProgress->getExceptionCount());
        static::assertSame($migrationProgress, $repository->updates[0][0]['progress']);
        static::assertSame(MigrationProgressStatus::ABORTING->value, $migrationProgress->getStepValue());
        static::assertSame(SwagMigrationRunEntity::STATUS_ABORTED, $run->getStatus());
        static::assertCount(1, $dummyLoggingService->getLoggingArray());
    }

    #[DataProvider('getOnWorkerMessageHandledProvider')]
    public function testOnWorkerMessageHandled(bool $noMessage, bool $noRun, bool $noProgress, int $exceptionCount, int $expectedExceptionCount, int $expectedUpdateCount): void
    {
        $migrationProgress = new MigrationProgress(
            MigrationProgressStatus::FETCHING,
            0,
            1,
            new ProgressDataSetCollection(),
            ProductDefinition::ENTITY_NAME,
            0,
            $exceptionCount
        );

        $runUuid = Uuid::randomHex();
        $run = new SwagMigrationRunEntity();
        $run->setStatus(SwagMigrationRunEntity::STATUS_RUNNING);
        $run->setId($runUuid);

        if (!$noProgress) {
            $run->setProgress($migrationProgress);
        }

        /** @var StaticEntityRepository<SwagMigrationRunCollection> $repository */
        $repository = new StaticEntityRepository(
            [
                new EntitySearchResult(
                    SwagMigrationRunDefinition::ENTITY_NAME,
                    1,
                    $noRun ? new EntityCollection([]) : new EntityCollection([$run]),
                    null,
                    new Criteria(),
                    $this->context
                ),
            ],
            new SwagMigrationRunDefinition()
        );

        $dummyLoggingService = new DummyLoggingService();
        $busMock = new CollectingMessageBus();
        $event = new WorkerMessageHandledEvent(
            new Envelope($noMessage ? new \stdClass() : new MigrationProcessMessage($this->context, $runUuid)),
            '',
        );
        $subscriber = new MessageQueueSubscriber(
            $busMock,
            $repository,
            $dummyLoggingService
        );

        $subscriber->onWorkerMessageHandled($event);

        static::assertSame($expectedExceptionCount, $migrationProgress->getExceptionCount());
        static::assertCount($expectedUpdateCount, $repository->updates);
    }

    /**
     * @return \Generator<string, array{noMessage: bool, noRun: bool, noProgress: bool, exceptionCount: int, expectedExceptionCount: int, expectedUpdateCount: int}>
     */
    public static function getOnWorkerMessageHandledProvider(): \Generator
    {
        yield 'Without MigrationProcessMessage should do nothing' => [
            'noMessage' => true,
            'noRun' => false,
            'noProgress' => false,
            'exceptionCount' => 1,
            'expectedExceptionCount' => 1,
            'expectedUpdateCount' => 0,
        ];

        yield 'Without run should do nothing' => [
            'noMessage' => false,
            'noRun' => true,
            'noProgress' => false,
            'exceptionCount' => 1,
            'expectedExceptionCount' => 1,
            'expectedUpdateCount' => 0,
        ];

        yield 'Without progress should do nothing' => [
            'noMessage' => false,
            'noRun' => false,
            'noProgress' => true,
            'exceptionCount' => 1,
            'expectedExceptionCount' => 1,
            'expectedUpdateCount' => 0,
        ];

        yield 'With MigrationProcessMessage and exceptionCount = 0 should do nothing' => [
            'noMessage' => false,
            'noRun' => false,
            'noProgress' => false,
            'exceptionCount' => 0,
            'expectedExceptionCount' => 0,
            'expectedUpdateCount' => 0,
        ];

        yield 'With MigrationProcessMessage and exceptionCount > 1 should reset exceptionCount to 0' => [
            'noMessage' => false,
            'noRun' => false,
            'noProgress' => false,
            'exceptionCount' => 1,
            'expectedExceptionCount' => 0,
            'expectedUpdateCount' => 1,
        ];
    }
}
