<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\AdvanceMediaStepHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\AdvanceMediaStepMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;

#[Package('fundamentals@after-sales')]
class AdvanceMediaStepHandlerTest extends TestCase
{
    public function testMediaProcessingNotFinished(): void
    {
        $messageBus = new CollectingMessageBus();

        $runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $runTransitionService->expects(static::never())->method('transitionToRunStep');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationMediaFileDefinition::ENTITY_NAME,
                10,
                new EntityCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $handler = new AdvanceMediaStepHandler(
            $messageBus,
            $runTransitionService,
            $repository,
        );

        $message = new AdvanceMediaStepMessage(
            Context::createDefaultContext(),
            Uuid::randomHex()
        );

        $handler->__invoke($message);

        static::assertCount(1, $messageBus->getMessages());

        $message = $messageBus->getMessages()[0]->getMessage();

        static::assertInstanceOf(AdvanceMediaStepMessage::class, $message);
    }

    public function testMediaProcessingFinished(): void
    {
        $messageBus = new CollectingMessageBus();

        $runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $runTransitionService->expects(static::once())->method('transitionToRunStep');

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationMediaFileDefinition::ENTITY_NAME,
                0,
                new EntityCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $handler = new AdvanceMediaStepHandler(
            $messageBus,
            $runTransitionService,
            $repository,
        );

        $message = new AdvanceMediaStepMessage(
            Context::createDefaultContext(),
            Uuid::randomHex()
        );

        $handler->__invoke($message);

        static::assertCount(1, $messageBus->getMessages());

        $message = $messageBus->getMessages()[0]->getMessage();

        static::assertInstanceOf(MigrationProcessMessage::class, $message);
    }
}
