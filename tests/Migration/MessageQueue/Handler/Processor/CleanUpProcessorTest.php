<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler\Processor;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\CleanUpProcessor;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class CleanUpProcessorTest extends TestCase
{
    private CleanUpProcessor $processor;

    private CollectingMessageBus $bus;

    private MockObject&Connection $dbalConnection;

    protected function setUp(): void
    {
        $this->dbalConnection = $this->createMock(Connection::class);
        $this->bus = new CollectingMessageBus();
        $this->processor = new CleanUpProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->dbalConnection,
            $this->bus
        );
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

        $this->dbalConnection
            ->method('executeStatement')
            ->willReturn(10);

        $this->processor->process(
            $migrationContext,
            Context::createDefaultContext(),
            $run,
            $progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }
}
