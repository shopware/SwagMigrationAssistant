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
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessHandler;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\MigrationProcessorRegistry;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MigrationProcessorInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('services-settings')]
class MigrationProcessHandlerTest extends TestCase
{
    private MigrationProcessHandler $migrationProcessHandler;

    protected function setUp(): void
    {
        $this->migrationProcessHandler = new MigrationProcessHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class)
        );
    }

    public function testInvokeWithoutRun(): void
    {
        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::NO_RUNNING_MIGRATION, $exception->getErrorCode());
        }
    }

    public function testInvokeWithoutRunProgress(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class)
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::NO_RUN_PROGRESS_FOUND, $exception->getErrorCode());
        }
    }

    public function testInvokeWithoutMigrationContext(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress(new MigrationProgress(0, 100, new ProgressDataSetCollection(), 'product', 0));

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $this->createMock(MigrationContextFactoryInterface::class),
            $this->createMock(MigrationProcessorRegistry::class)
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        try {
            $this->migrationProcessHandler->__invoke($message);
        } catch (MigrationException $exception) {
            static::assertSame(MigrationException::MIGRATION_CONTEXT_NOT_CREATED, $exception->getErrorCode());
        }
    }

    public function testInvoke(): void
    {
        $run = new SwagMigrationRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setProgress(new MigrationProgress(0, 100, new ProgressDataSetCollection(), 'product', 0));
        $run->setStep(MigrationStep::FETCHING);

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationRunDefinition::ENTITY_NAME,
                1,
                new EntityCollection([$run]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $processorRegistry = $this->createMock(MigrationProcessorRegistry::class);
        $processorRegistry
            ->expects(static::once())
            ->method('getProcessor')
            ->willReturn($this->createMock(MigrationProcessorInterface::class));

        $migrationContextFactory = $this->createMock(MigrationContextFactoryInterface::class);
        $migrationContextFactory->method('create')->willReturn(new MigrationContext(new Shopware55Profile()));

        $this->migrationProcessHandler = new MigrationProcessHandler(
            $repository,
            $migrationContextFactory,
            $processorRegistry
        );

        $message = new MigrationProcessMessage(Context::createDefaultContext(), Uuid::randomHex());

        $this->migrationProcessHandler->__invoke($message);
    }
}
