<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;

#[Package('services-settings')]
class LoggingService implements LoggingServiceInterface
{
    protected array $logging = [];

    /**
     * @param EntityRepository<SwagMigrationLoggingCollection> $loggingRepo
     */
    public function __construct(
        private readonly EntityRepository $loggingRepo,
        private readonly LoggerInterface $logger
    ) {
    }

    public function reset(): void
    {
        if (!empty($this->logging)) {
            $this->logger->error('SwagMigrationAssistant: Migration logging was not empty on calling reset.');
        }

        $this->logging = [];
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        try {
            $this->loggingRepo->create($this->logging, $context);
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->logging = [];
        }
    }

    public function addLogEntry(LogEntryInterface $logEntry): void
    {
        $this->logging[] = [
            'level' => $logEntry->getLevel(),
            'code' => $logEntry->getCode(),
            'title' => $logEntry->getTitle(),
            'description' => $logEntry->getDescription(),
            'parameters' => $logEntry->getParameters(),
            'titleSnippet' => $logEntry->getTitleSnippet(),
            'descriptionSnippet' => $logEntry->getDescriptionSnippet(),
            'entity' => $logEntry->getEntity(),
            'sourceId' => $logEntry->getSourceId(),
            'runId' => $logEntry->getRunId(),
        ];
    }

    private function writePerEntry(Context $context): void
    {
        foreach ($this->logging as $log) {
            try {
                $this->loggingRepo->create([$log], $context);
            } catch (\Exception) {
                $this->logger->error('SwagMigrationAssistant: Could not write log entry: ', $log);
            }
        }
    }
}
