<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Exception\NoConnectionFoundException;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ProcessorNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('services-settings')]
final class ProcessMediaHandler
{
    final public const MEDIA_ERROR_THRESHOLD = 3;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
        private readonly MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry,
        private readonly LoggingServiceInterface $loggingService,
        private readonly MigrationContextFactoryInterface $migrationContextFactory
    ) {
    }

    /**
     * @throws MigrationException
     */
    public function __invoke(ProcessMediaMessage $message): void
    {
        $context = $message->getContext();

        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunId()]), $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $connection = $run->getConnection();
        if ($connection === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $migrationContext = $this->migrationContextFactory->create($run, 0, 0, $message->getEntityName());

        if ($migrationContext === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $workload = [];
        foreach ($message->getMediaFileIds() as $mediaFileId) {
            $workload[] = new MediaProcessWorkloadStruct(
                $mediaFileId,
                $message->getRunId(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE
            );
        }

        try {
            $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
            $workload = $processor->process($migrationContext, $context, $workload);
            $this->processFailures($context, $migrationContext, $processor, $workload);
        } catch (NoConnectionFoundException $e) {
            $this->loggingService->addLogEntry(new ProcessorNotFoundLog(
                $message->getRunId(),
                $message->getEntityName(),
                $connection->getProfileName(),
                $connection->getGatewayName()
            ));

            $this->loggingService->saveLogging($context);
        } catch (\Exception $e) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $message->getRunId(),
                $message->getEntityName(),
                $e
            ));

            $this->loggingService->saveLogging($context);
        }
    }

    /**
     * @param MediaProcessWorkloadStruct[] $workload
     */
    private function processFailures(
        Context $context,
        MigrationContextInterface $migrationContext,
        MediaFileProcessorInterface $processor,
        array $workload
    ): void {
        for ($i = 0; $i < self::MEDIA_ERROR_THRESHOLD; ++$i) {
            $errorWorkload = [];

            foreach ($workload as $item) {
                if ($item->getErrorCount() > 0) {
                    $errorWorkload[] = $item;
                }
            }

            if (empty($errorWorkload)) {
                break;
            }

            $workload = $processor->process($migrationContext, $context, $errorWorkload);
        }
    }
}
