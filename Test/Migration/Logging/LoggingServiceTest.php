<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Logging;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\AssociationRequiredMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertChildEntity;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;

class LoggingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var LoggingService
     */
    private $loggingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $runUuid;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->loggingService = new LoggingService($this->loggingRepo);

        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->runUuid = Uuid::randomHex();
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => 'inProgress',
                ],
            ],
            $this->context
        );
    }

    public function testAddLogEntry(): void
    {
        $log1 = new AssociationRequiredMissingLog($this->runUuid, DefaultEntities::PRODUCT, '2', DefaultEntities::PRODUCT_MANUFACTURER);
        $log2 = new CannotConvertChildEntity($this->runUuid, DefaultEntities::PRODUCT_MANUFACTURER, DefaultEntities::PRODUCT, '200');

        $this->loggingService->addLogEntry($log1);
        $this->loggingService->addLogEntry($log2);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->saveLogging($this->context);
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        $validCount = 0;
        /** @var SwagMigrationLoggingEntity $element */
        foreach ($result->getElements() as $element) {
            if ($log1->getCode() === $element->getCode() || $log2->getCode() === $element->getCode()) {
                ++$validCount;
            }
        }
        static::assertSame(2, $validCount);
    }
}
