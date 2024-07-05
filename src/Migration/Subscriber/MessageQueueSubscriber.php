<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Subscriber;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\MessageQueueExceptionLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunAbortedAutomatically;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class MessageQueueSubscriber implements EventSubscriberInterface
{
    private const MAX_EXCEPTION_COUNT = 3;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $runRepo
     */
    public function __construct(
        private MessageBusInterface $bus,
        private EntityRepository $runRepo,
        private LoggingServiceInterface $loggingService,
        private readonly RunTransitionServiceInterface $runTransitionService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
        ];
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        /*
         * If no MigrationProcessMessage is found in the envelope, we don't want to do anything.
         */
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof MigrationProcessMessage) {
            return;
        }

        /*
         * If no run is found, we don't want to do anything.
         */
        $run = $this->getRunFromMessage($message);
        if ($run === null) {
            return;
        }

        /*
         * If no progress is found, we don't want to do anything.
         */
        $progress = $run->getProgress();
        if ($progress === null) {
            return;
        }

        /*
         * If the run is already in aborted state, we don't want to do anything.
         */
        if ($run->getStep() === MigrationStep::ABORTED) {
            return;
        }

        /*
         * Raise exception counter and log the exception
         */
        $progress->raiseExceptionCount();
        $this->loggingService->addLogEntry(new MessageQueueExceptionLog($run->getId(), $event->getThrowable(), $progress->getExceptionCount()));

        /*
         * Check if run is already in aborting state and failed again there, then set run status to aborted and log the error.
         */
        if ($run->getStep() === MigrationStep::ABORTING) {
            $this->runTransitionService->forceTransitionToRunStep($run->getId(), MigrationStep::ABORTED);
            $progress->setIsAborted(true);
            $this->updateRun($run->getId(), $progress, $message->getContext());

            $this->loggingService->addLogEntry(new RunAbortedAutomatically($run->getId(), $event->getThrowable()));
            $this->loggingService->saveLogging($message->getContext());

            return;
        }

        /*
         * Check if exception counter is greater than MAX_EXCEPTION_COUNT and
         * if so, transition to aborting automatically.
         * else, just save the new exception count and retry.
         */
        if ($progress->getExceptionCount() > self::MAX_EXCEPTION_COUNT) {
            $this->runTransitionService->forceTransitionToRunStep($run->getId(), MigrationStep::ABORTING);
            $this->updateRun($run->getId(), $progress, $message->getContext());
        } else {
            $this->updateRun($run->getId(), $progress, $message->getContext());
        }

        /*
         * If message is retried, we do not have to put another message into the queue.
         */
        if ($event->willRetry()) {
            return;
        }

        /*
         * If message is not retried, we have to set the message to retry.
         */
        $this->bus->dispatch($message);
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        /*
         * If no MigrationProcessMessage is found in the envelope, we don't want to do anything.
         */
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof MigrationProcessMessage) {
            return;
        }

        /*
         * If no run is found, we don't want to do anything.
         */
        $run = $this->getRunFromMessage($message);
        if ($run === null) {
            return;
        }

        /*
         * If no progress is found, we don't want to do anything.
         */
        $progress = $run->getProgress();
        if ($progress === null || $progress->getExceptionCount() === 0) {
            return;
        }

        $progress->resetExceptionCount();

        /*
        * Reset exception counter, if message was handled successfully.
        */
        $this->updateRun($run->getId(), $progress, $message->getContext());
    }

    private function getRunFromMessage(MigrationProcessMessage $message): ?SwagMigrationRunEntity
    {
        return $this->runRepo->search(new Criteria([$message->getRunUuid()]), $message->getContext())->getEntities()->first();
    }

    private function updateRun(string $runUuid, ?MigrationProgress $progress, Context $context): void
    {
        $data = [
            'id' => $runUuid,
        ];

        if ($progress !== null) {
            $data['progress'] = $progress;
        }

        $this->runRepo->update(
            [
                $data,
            ],
            $context
        );
    }
}
