<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;

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
     * @var RepositoryInterface
     */
    private $loggingRepo;

    public function __construct(RepositoryInterface $loggingRepo)
    {
        $this->loggingRepo = $loggingRepo;
    }

    public function addInfo(string $runId, string $title, string $description, array $details = null): void
    {
        $this->logging[] = [
            'runId' => $runId,
            'type' => self::INFO_TYPE,
            'logEntry' => [
                'title' => $title,
                'description' => $description,
                'details' => $details,
            ],
        ];
    }

    public function addWarning(string $runId, string $title, string $description, array $details = null): void
    {
        $this->logging[] = [
            'runId' => $runId,
            'type' => self::WARNING_TYPE,
            'logEntry' => [
                'title' => $title,
                'description' => $description,
                'details' => $details,
            ],
        ];
    }

    public function addError(string $runId, string $code, string $title, array $details = null): void
    {
        $this->logging[] = [
            'runId' => $runId,
            'type' => self::ERROR_TYPE,
            'logEntry' => [
                'code' => $code,
                'title' => $title,
                'description' => $title,
                'details' => $details,
            ],
        ];
    }

    public function saveLogging(Context $context): void
    {
        if (empty($this->logging)) {
            return;
        }

        $this->loggingRepo->create($this->logging, $context);

        $this->logging = [];
    }
}
