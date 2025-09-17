<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('fundamentals@after-sales')]
class ProcessMediaHandlerTest extends TestCase
{
    public function testProcess(): void
    {
        $mediaFileIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $progress = new MigrationProgress(
            100,
            200,
            new ProgressDataSetCollection(),
            ProductDefinition::ENTITY_NAME,
            10
        );

        $migrationRun = new SwagMigrationRunEntity();
        $migrationRun->setId(Uuid::randomHex());
        $migrationRun->setConnection($connection);
        $migrationRun->setProgress($progress);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$migrationRun]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $repository->expects(static::once())->method('update');

        $processMediaMessage = new ProcessMediaMessage(
            $mediaFileIds,
            $migrationRun->getId(),
            ProductDefinition::ENTITY_NAME,
            Context::createDefaultContext()
        );

        $processor = $this->createMock(MediaFileProcessorInterface::class);
        $processor->expects(static::once())->method('process')->willReturn([]);

        $registry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $registry->method('getProcessor')->willReturn($processor);

        $logger = $this->createMock(LoggingServiceInterface::class);

        $migrationContext = new MigrationContext(new Shopware55Profile());

        $migrationContextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $migrationContextFactory->method('create')->willReturn($migrationContext);

        $handler = new ProcessMediaHandler(
            $repository,
            $registry,
            $logger,
            $migrationContextFactory
        );

        $handler->__invoke($processMediaMessage);

        static::assertSame(103, $migrationRun->getProgress()?->getProgress());
        static::assertSame(13, $migrationRun->getProgress()?->getCurrentEntityProgress());
    }
}

