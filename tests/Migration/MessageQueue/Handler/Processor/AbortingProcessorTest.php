<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler\Processor;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\AbortingProcessor;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class AbortingProcessorTest extends TestCase
{
    private AbortingProcessor $processor;

    private CollectingMessageBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();
        $this->processor = new AbortingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->createMock(RunServiceInterface::class),
            $this->bus
        );
    }

    public function testProcessingWithoutConnection(): void
    {
        $progress = new MigrationProgress(0, 0, new ProgressDataSetCollection(), 'product', 0);

        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress($progress);

        try {
            $this->processor->process(
                $this->createMock(MigrationContextInterface::class),
                Context::createDefaultContext(),
                $run,
                $progress
            );
        } catch (MigrationException $e) {
            static::assertSame(MigrationException::NO_CONNECTION_FOUND, $e->getErrorCode());
            static::assertCount(0, $this->bus->getMessages());

            return;
        }
    }

    public function testProcessing(): void
    {
        $progress = new MigrationProgress(0, 0, new ProgressDataSetCollection(), 'product', 0);

        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress($progress);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(new Shopware55Profile(), $connection);

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }
}
