<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;

class LoggingService implements LoggingServiceInterface
{
    public const ERROR_TYPE = 'error';
    public const WARNING_TYPE = 'warning';
    public const INFO_TYPE = 'info';

    /**
     * @var array
     */
    protected $logging = [];

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    public function __construct(EntityRepositoryInterface $loggingRepo)
    {
        $this->loggingRepo = $loggingRepo;
    }

    public function addInfo(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
    }

    public function addWarning(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
    }

    public function addError(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        $this->loggingRepo->create($this->logging, $context);

        $this->logging = [];
    }

    public function addLogEntry(LogEntryInterface $logEntry): void
    {
        $this->addLog(
            $logEntry->getLevel(),
            $logEntry->getCode(),
            $logEntry->getTitle(),
            $logEntry->getDescription(),
            $logEntry->getDescriptionArguments(),
            $logEntry->getTitleSnippet(),
            $logEntry->getDescriptionSnippet(),
            $logEntry->getEntity(),
            $logEntry->getSourceId(),
            $logEntry->getRunId()
        );
    }

    private function addLog(
        string $level,
        string $code,
        string $title,
        string $description,
        array $descriptionArguments,
        string $titleSnippet,
        string $descriptionSnippet,
        ?string $entity,
        ?string $sourceId,
        ?string $runId
    ): void {
        $this->logging[] = [
            'level' => $level,
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'descriptionArguments' => $descriptionArguments,
            'titleSnippet' => $titleSnippet,
            'descriptionSnippet' => $descriptionSnippet,
            'entity' => $entity,
            'sourceId' => $sourceId,
            'runId' => $runId,
        ];
    }
}
