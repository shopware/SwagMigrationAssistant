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
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
#[Package('services-settings')]
/**
 * @internal
 */
final class MigrationProcessHandler
{
    private int $batchSize = 100;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly MigrationProcessorRegistry $processorRegistry
    ) {
    }

    public function __invoke(MigrationProcessMessage $message): void
    {
        $context = $message->getContext();
        $run = $this->getCurrentRun($message, $context);
        $progress = $run->getProgress();

        if ($progress === null) {
            throw MigrationException::noRunProgressFound($run->getId());
        }

        $migrationContext = $this->migrationContextFactory->create($run, $progress->getCurrentEntityProgress(), $this->batchSize, $progress->getCurrentEntity());

        if ($migrationContext === null) {
            throw MigrationException::migrationContextNotCreated();
        }

        $processor = $this->processorRegistry->getProcessor($run->getStep());
        $processor->process($migrationContext, $context, $run, $progress);
    }

    private function getCurrentRun(MigrationProcessMessage $message, Context $context): SwagMigrationRunEntity
    {
        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunUuid()]), $context)->getEntities()->first();

        if ($run === null) {
            throw MigrationException::noRunningMigration($message->getRunUuid());
        }

        return $run;
    }
}
