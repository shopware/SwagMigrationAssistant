<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\ShopwareHttpException;

class ExceptionRunLog extends BaseRunLogEntry
{
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(string $runId, string $entity, \Exception $exception, ?string $sourceId = null)
    {
        parent::__construct($runId, $entity, $sourceId);
        $this->exception = $exception;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION_RUN_EXCEPTION';
    }

    public function getTitle(): string
    {
        return 'An exception occurred during a migration';
    }

    public function getDescriptionArguments(): array
    {
        $errorCode = $this->exception->getCode();
        if (is_subclass_of($this->exception, ShopwareHttpException::class)) {
            $errorCode = $this->exception->getErrorCode();
        }

        return [
            'exceptionCode' => $errorCode,
            'exceptionFile' => $this->exception->getFile(),
            'exceptionLine' => $this->exception->getLine(),
            'exceptionTrace' => $this->exception->getTraceAsString(),
        ];
    }

    public function getDescription(): string
    {
        return $this->exception->getMessage();
    }

    public function getTitleSnippet(): string
    {
        return '...';
    }

    public function getDescriptionSnippet(): string
    {
        return '...';
    }
}
