<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

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
        $this->addLog($runId, self::INFO_TYPE, $code, $title, $description, $details, $counting);
    }

    public function addWarning(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
        $this->addLog($runId, self::WARNING_TYPE, $code, $title, $description, $details, $counting);
    }

    public function addError(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
        $this->addLog($runId, self::ERROR_TYPE, $code, $title, $description, $details, $counting);
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        $this->loggingRepo->create($this->logging, $context);

        $this->logging = [];
    }

    private function addLog(string $runId, string $type, string $code, string $title, string $description, array $details = [], int $counting = 0): void
    {
        $details['count'] = $counting;

        $this->logging[] = [
            'runId' => $runId,
            'type' => $type,
            'logEntry' => [
                'code' => $code,
                'title' => $title,
                'description' => $description,
                'details' => $details,
            ],
        ];
    }
}
