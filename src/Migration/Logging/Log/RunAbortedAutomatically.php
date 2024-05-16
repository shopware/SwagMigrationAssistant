<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;

#[Package('services-settings')]
class RunAbortedAutomatically extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        private readonly \Throwable $exception,
    ) {
        parent::__construct($runId);
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION_RUN_ABORTED_AUTOMATICALLY_EXCEPTION';
    }

    public function getTitle(): string
    {
        return 'Run was aborted automatically, because too many consecutive exceptions occurred';
    }

    /**
     * @return array{exceptionCode: int|string, exceptionMessage: ?string, exceptionFile: string, exceptionLine: int, exceptionTrace: ?string, description: string}
     */
    public function getParameters(): array
    {
        $errorCode = $this->exception->getCode();
        if (\is_subclass_of($this->exception, ShopwareHttpException::class)) {
            $errorCode = $this->exception->getErrorCode();
        }

        return [
            'exceptionCode' => $errorCode,
            'exceptionMessage' => \preg_replace('/[[:^print:]]/', '', $this->exception->getMessage()),
            'exceptionFile' => $this->exception->getFile(),
            'exceptionLine' => $this->exception->getLine(),
            'exceptionTrace' => \preg_replace('/[[:^print:]]/', '', $this->exception->getTraceAsString()),
            'description' => \sprintf(
                'RunId: %s ' . \PHP_EOL . '%s',
                $this->getRunId(),
                \preg_replace('/[[:^print:]]/', '', $this->exception->getMessage())
            ),
        ];
    }

    public function getDescription(): string
    {
        return $this->getParameters()['description'];
    }
}
