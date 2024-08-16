<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class FetchingProcessor extends AbstractProcessor
{
    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepo
     * @param EntityRepository<SwagMigrationMediaFileCollection> $migrationMediaFileRepo
     */
    public function __construct(
        EntityRepository $migrationRunRepo,
        EntityRepository $migrationDataRepo,
        EntityRepository $migrationMediaFileRepo,
        RunTransitionServiceInterface $runTransitionService,
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly MigrationDataConverterInterface $migrationDataConverter,
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct(
            $migrationRunRepo,
            $migrationDataRepo,
            $migrationMediaFileRepo,
            $runTransitionService
        );
    }

    public function supports(MigrationStep $step): bool
    {
        return $step === MigrationStep::FETCHING;
    }

    public function process(
        MigrationContextInterface $migrationContext,
        Context $context,
        SwagMigrationRunEntity $run,
        MigrationProgress $progress
    ): void {
        $runId = $migrationContext->getRunUuid();
        $totalCountOfCurrentEntity = $progress->getDataSets()->getTotalByEntityName($progress->getCurrentEntity());
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

        if ($progress->getCurrentEntityProgress() >= $totalCountOfCurrentEntity) {
            $this->changeProgressToNextEntity($run, $progress, $context);
            $this->updateProgress($runId, $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $this->migrationDataConverter->convert($data, $migrationContext, $context);

        // increase "currentEntityProgress" by the batch limit,
        // so next process iteration will handle the next batch
        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + $migrationContext->getLimit());
        // increase the overall (step) "progress" by the actual amount of entities processed,
        // so together with the total amount of entities (which we get upfront) a percentage can be calculated and progress bar shown (in the admin UI / CLI)
        $progress->setProgress($progress->getProgress() + \count($data));

        $this->updateProgress($runId, $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }
}
