<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MessageQueue\Message\AdvanceMediaStepMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
final class AdvanceMediaStepHandler
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly RunTransitionServiceInterface $runTransitionService,
        private readonly EntityRepository $migrationMediaFileRepo,
    ) {
    }

    public function __invoke(AdvanceMediaStepMessage $message): void
    {
        $context = $message->getContext();

        if ($this->isAllMediaProcessed($context, $message->getRunId())) {
            $this->runTransitionService->transitionToRunStep($message->getRunId(), MigrationStep::CLEANUP);
            $this->messageBus->dispatch(new MigrationProcessMessage($context, $message->getRunId()));

            return;
        }

        $this->messageBus->dispatch(
            new AdvanceMediaStepMessage($context, $message->getRunId()),
            [new DelayStamp(5000)]
        );
    }

    private function isAllMediaProcessed(Context $context, string $runId): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('runId', $runId)
        );
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('processed', false),
                    new EqualsFilter('processFailure', false),
                ]
            )
        );

        $unprocessedCount = $this->migrationMediaFileRepo->search($criteria, $context)->getTotal();

        return $unprocessedCount === 0;
    }
}
