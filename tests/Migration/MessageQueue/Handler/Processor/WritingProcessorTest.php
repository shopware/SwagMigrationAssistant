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
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\WritingProcessor;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSet;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class WritingProcessorTest extends TestCase
{
    private WritingProcessor $processor;

    private CollectingMessageBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();
        $this->processor = new WritingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->createMock(MigrationDataWriter::class),
            $this->bus
        );
    }

    public function testProcessingWithoutData(): void
    {
        $progress = new MigrationProgress(0, 0, new ProgressDataSetCollection(), 'product', 0);
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress($progress);
        $run->setStep(MigrationStep::FETCHING);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(new Shopware55Profile(), $connection);

        $this->processor = new WritingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->createMock(MigrationDataWriter::class),
            $this->bus
        );

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }

    public function testProcessing(): void
    {
        $progress = new MigrationProgress(
            0,
            0,
            new ProgressDataSetCollection([
                'product' => new ProgressDataSet('product', 1000),
            ]),
            'product',
            100
        );

        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress($progress);
        $run->setStep(MigrationStep::FETCHING);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $migrationContext = new MigrationContext(new Shopware55Profile(), $connection);

        $migrationDataWriter = $this->createMock(MigrationDataWriter::class);
        $migrationDataWriter
            ->method('writeData')
            ->willReturn(100);

        $this->processor = new WritingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $migrationDataWriter,
            $this->bus
        );

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        $progress = $run->getProgress();
        static::assertNotNull($progress);
        static::assertCount(1, $this->bus->getMessages());
        static::assertSame(200, $progress->getCurrentEntityProgress());
    }
}
