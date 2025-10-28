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
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\AbortingProcessor;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('fundamentals@after-sales')]
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
            $this->bus
        );
    }

    public function testProcessing(): void
    {
        $runId = Uuid::randomHex();
        $connectionId = Uuid::randomHex();
        $currentEntity = 'product';

        $progress = new MigrationProgress(0, 0, new ProgressDataSetCollection(), $currentEntity, 0);

        $run = new SwagMigrationRunEntity();
        $run->setId($runId);
        $run->setProgress($progress);

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId($connectionId);

        $migrationContext = new MigrationContext($connection, new Shopware55Profile());
        $context = Context::createDefaultContext();

        $this->processor->process(
            $migrationContext,
            $context,
            $run,
            $progress
        );

        $messages = $this->bus->getMessages();
        static::assertCount(1, $messages);

        $message = $messages[0]->getMessage();
        static::assertInstanceOf(ResetChecksumMessage::class, $message);

        static::assertSame($connectionId, $message->getConnectionId());
        static::assertSame($context, $message->getContext());
        static::assertTrue($message->isResettingAll());
        static::assertSame($runId, $message->getRunId());
        static::assertSame($currentEntity, $message->getEntity());
        static::assertTrue($message->isPartOfAbort());
    }
}
