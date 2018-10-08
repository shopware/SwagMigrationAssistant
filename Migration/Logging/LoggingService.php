<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Logging;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;

class LoggingService implements LoggingServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $errorLogRepo;

    public function __construct(RepositoryInterface $errorLogRepo)
    {
        $this->errorLogRepo = $errorLogRepo;
    }

    public function addError(Context $context, string $runId, string $title, array $details = NULL): void
    {
        $error = [
            'runId' => $runId,
            'type' => 'error',
            'logEntry' => [
                'title' => $title,
                'details' => $details
            ]
        ];

        $this->errorLogRepo->create([ $error ], $context);
    }
}