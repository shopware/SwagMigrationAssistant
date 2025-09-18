<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MessageQueue\Message\AbortCompletionMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
final readonly class AbortCompletionHandler
{
    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private RunTransitionServiceInterface $runTransitionService,
        private MessageBusInterface $bus,
        private EntityRepository $migrationRunRepo,
    ) {
    }

    public function __invoke(AbortCompletionMessage $message): void
    {
        $runId = $message->getRunId();
        $context = $message->getContext();

        $this->runTransitionService->forceTransitionToRunStep(
            $runId,
            MigrationStep::CLEANUP
        );

        $progress = $message->getProgress();
        $progress->setIsAborted(true);

        $this->migrationRunRepo->upsert([
            [
                'id' => $runId,
                'progress' => $progress,
            ],
        ], $context);

        $this->bus->dispatch(new MigrationProcessMessage(
            $context,
            $runId
        ));
    }
}
