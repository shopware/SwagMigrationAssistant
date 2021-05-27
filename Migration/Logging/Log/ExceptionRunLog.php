<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\ShopwareHttpException;

class ExceptionRunLog extends BaseRunLogEntry
{
    /**
     * @var \Throwable
     */
    private $exception;

    public function __construct(string $runId, string $entity, \Throwable $exception, ?string $sourceId = null)
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
        return 'An exception occurred';
    }

    public function getParameters(): array
    {
        $entity = $this->getEntity() ?? '-';
        $errorCode = $this->exception->getCode();
        if (\is_subclass_of($this->exception, ShopwareHttpException::class)) {
            $errorCode = $this->exception->getErrorCode();
        }

        return [
            'entity' => $this->getEntity(),
            'sourceId' => $this->getSourceId(),
            'exceptionCode' => $errorCode,
            'exceptionMessage' => \preg_replace('/[[:^print:]]/', '', $this->exception->getMessage()),
            'exceptionFile' => $this->exception->getFile(),
            'exceptionLine' => $this->exception->getLine(),
            'exceptionTrace' => \preg_replace('/[[:^print:]]/', '', $this->exception->getTraceAsString()),
            'description' => \sprintf(
                'Entity: %s, sourceId: %s' . \PHP_EOL . '%s',
                $entity,
                $this->getSourceId() ?? '-',
                \preg_replace('/[[:^print:]]/', '', $this->exception->getMessage())
            ),
        ];
    }

    public function getDescription(): string
    {
        return $this->getParameters()['description'];
    }
}
