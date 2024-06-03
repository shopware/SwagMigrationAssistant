<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class RunTransitionServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private readonly RunTransitionService $runTransitionService;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private readonly EntityRepository $runRepository;

    private readonly Context $context;

    protected function setUp(): void
    {
        $this->runRepository = $this->getContainer()->get('swag_migration_run.repository');
        $this->context = Context::createDefaultContext();

        $this->runTransitionService = new RunTransitionService($this->getContainer()->get(Connection::class));
    }

    #[DataProvider('transitionToRunStepProvider')]
    public function testTransitionToRunStep(
        MigrationStep $fromStep,
        MigrationStep $toStep,
        MigrationStep $expectedStep,
        bool $forceTransition,
    ): void {
        $runId = Uuid::randomHex();
        $this->runRepository->create([
            [
                'id' => $runId,
                'step' => $fromStep->value,
            ],
        ], $this->context);

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->runRepository->search(new Criteria([$runId]), $this->context)->getEntities()->get($runId);
        static::assertNotNull($run);
        static::assertSame($fromStep, $run->getStep());

        if ($forceTransition) {
            $this->runTransitionService->forceTransitionToRunStep($runId, $toStep);
        } else {
            $this->runTransitionService->transitionToRunStep($runId, $toStep);
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->runRepository->search(new Criteria([$runId]), $this->context)->getEntities()->get($runId);
        static::assertNotNull($run);
        static::assertSame($expectedStep, $run->getStep());
    }

    public static function transitionToRunStepProvider(): \Generator
    {
        yield 'fetching to writing' => [
            'fromStep' => MigrationStep::FETCHING,
            'toStep' => MigrationStep::WRITING,
            'expectedStep' => MigrationStep::WRITING,
            'forceTransition' => false,
        ];
        yield 'aborting to writing should be prevented' => [
            'fromStep' => MigrationStep::ABORTING,
            'toStep' => MigrationStep::WRITING,
            'expectedStep' => MigrationStep::ABORTING,
            'forceTransition' => false,
        ];
        yield 'forcing fetching to writing' => [
            'fromStep' => MigrationStep::FETCHING,
            'toStep' => MigrationStep::WRITING,
            'expectedStep' => MigrationStep::WRITING,
            'forceTransition' => true,
        ];
        yield 'forcing aborting to cleanup should work' => [
            'fromStep' => MigrationStep::ABORTING,
            'toStep' => MigrationStep::CLEANUP,
            'expectedStep' => MigrationStep::CLEANUP,
            'forceTransition' => true,
        ];
    }
}
