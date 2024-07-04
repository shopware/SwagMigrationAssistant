<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
abstract class AbstractProcessor implements MigrationProcessorInterface
{
    public function __construct(
        /**
         * @var EntityRepository<SwagMigrationRunCollection>
         */
        protected readonly EntityRepository $migrationRunRepo,
        /**
         * @var EntityRepository<SwagMigrationDataCollection>
         */
        protected readonly EntityRepository $migrationDataRepo,
        /**
         * @var EntityRepository<SwagMigrationMediaFileCollection>
         */
        protected readonly EntityRepository $migrationMediaFileRepo,
        protected readonly RunTransitionServiceInterface $runTransitionService
    ) {
    }

    protected function updateProgress(string $runId, MigrationProgress $progress, Context $context): void
    {
        $this->migrationRunRepo->update([[
            'id' => $runId,
            'progress' => $progress->jsonSerialize(),
        ]], $context);
    }

    protected function changeProgressToNextEntity(SwagMigrationRunEntity $run, MigrationProgress $progress, Context $context): void
    {
        $dataSets = \array_keys($progress->getDataSets()->getEntityNames());
        $currentIndex = \array_search($progress->getCurrentEntity(), $dataSets, true);

        $nextEntity = null;
        if ($currentIndex !== false && $currentIndex < \count($dataSets) - 1) {
            $nextEntity = $dataSets[$currentIndex + 1];
        }

        if ($nextEntity === null && $run->getStep() === MigrationStep::FETCHING) {
            $nextEntity = \current($dataSets);
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::WRITING);
            $progress->setProgress(0);
            $progress->setTotal($this->getWriteTotal($context));
        } elseif ($nextEntity === null && $run->getStep() === MigrationStep::WRITING) {
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::MEDIA_PROCESSING);
            $nextEntity = DefaultEntities::MEDIA;
            $progress->setProgress(0);
            $progress->setTotal($this->getMediaFileTotal($context));
        }

        $progress->setCurrentEntityProgress(0);
        $progress->setCurrentEntity((string) $nextEntity);
    }

    protected function getWriteTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('written', false));
        $criteria->addFilter(new EqualsFilter('convertFailure', false));
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);

        $result = $this->migrationDataRepo->aggregate($criteria, $context);
        $countResult = $result->get('count');

        if (!$countResult instanceof CountResult) {
            return 0;
        }

        return $countResult->getCount();
    }

    protected function getMediaFileTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->addFilter(new EqualsFilter('processed', false));
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);

        $result = $this->migrationMediaFileRepo->aggregate($criteria, $context);
        $countResult = $result->get('count');

        if (!$countResult instanceof CountResult) {
            return 0;
        }

        return $countResult->getCount();
    }
}
