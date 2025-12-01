<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertChildEntityLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Run\MigrationStep;

#[Package('fundamentals@after-sales')]
class LoggingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private LoggingService $loggingService;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    private Context $context;

    private string $runUuid;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->loggingService = new LoggingService($this->loggingRepo, new NullLogger());

        $runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->runUuid = Uuid::randomHex();
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => 'inProgress',
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $this->context
        );
    }

    public function testAddLogEntry(): void
    {
        $log1 = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(AssociationRequiredMissingLog::class);

        $log2 = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(CannotConvertChildEntityLog::class);

        $this->loggingService->addLogEntry($log1);
        $this->loggingService->addLogEntry($log2);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->saveLogging($this->context);
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        $validCount = 0;
        foreach ($result->getEntities() as $element) {
            if ($log1->getCode() === $element->getCode() || $log2->getCode() === $element->getCode()) {
                ++$validCount;
            }
        }
        static::assertSame(2, $validCount);
    }

    public function testAddLogEntryWithEntityId(): void
    {
        $entityId = Uuid::randomHex();
        $log = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))
            ->withEntityId($entityId)
            ->build(AssociationRequiredMissingLog::class);

        $this->loggingService->addLogEntry($log);
        $this->loggingService->saveLogging($this->context);
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(1, $result->getTotal());
        $resultLog = $result->getEntities()->first();
        static::assertInstanceOf(SwagMigrationLoggingEntity::class, $resultLog);
        static::assertSame($entityId, $resultLog->getEntityId());
    }
}
